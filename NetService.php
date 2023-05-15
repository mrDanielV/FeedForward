<?php
/*
	Класс инструментов обслуживания нейросети: 
		- Сохранение/чтение конфигов, 
		- Сохранение динамики обучения
		- Чтение файлов CSV с данными для обучения сети
		- График динамики обучения и т.п.

	Для построения графиков используется усеченная библиотека "JpGraph PHP library version 4.4.1"
		(C) 2000-2010 Asial Corporatoin
 */

require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');

class NetService {

	private $net = null;
	private $dir = null;

	public function __construct($net = null) {
		if($net instanceof FF){
			$this->net = $net;
		}
		
		return $this;
	}

	public function saveNet($filename = ''){
		// Сохранение файла конфигурации сети, если !$filename, то под именем сети
		if(!$this->net || empty($this->net->getNet())){
			return false;
		}

		// Имя файла и директория
		if(!$filename){
			$filename = 'config.json';
		}
		$filename = $this->getDir().$filename;

		// Данные
		$cfg = $this->net->getNet();
		$data = json_encode($cfg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		$data = self::prettyJSONBlockArr($data, 'layers');

		// Сохранение файла
		$r = $this->filewrite($filename, $data);
		if(!$r){
			return $r;
		}

		return $filename;
	}

	public function saveWs($filename = ''){
		// Сохранение файла весовых коэффициентов связей сети, если !$filename, то под именем сети
		if(!$this->net){
			return false;
		}
		
		$ws = $this->net->getWs();
		if(empty($ws)){
			return null;
		}

		// Имя файла и директория
		if(!$filename){
			$filename = 'weights.json';
		}
		$filename = $this->getDir().$filename;

		// Данные
		$data = json_encode($ws);


		// Сохранение файла
		$r = $this->filewrite($filename, $data);
		if(!$r){
			return $r;
		}

		return $filename;
	}

	public function read($filetype = 'config', $filename = ''){
		// Чтение файла нейросети - массив весов связей или файл конфигурации сети
		// $filetype = 'config'/ 'weights'
		if(!$filename && (!$this->net || !$this->net->get('name'))){
			return false;
		}

		if(!$filename){
			$filename = $filetype.'.json';
		}
		$filename = $this->getDir().$filename;

		$data = file_get_contents($filename);
		if(!$data){
			return null;
		}

		$result = json_decode($data, true);
		return $result;
	}

	public function graph($data = [], $return = 'img', $options = ['width' => 500, 'height' => 300]){
		// Функция построения графика по данным массива $data с использованием библиотеки JPGraph
		// Несколько графиков можно передать массивом $data[i => ['data' => $data_i, 'color'=>'', 'name'=>'legend'],  ...]
		if(!is_array($data)){
			$data = [];
		}
		$main = $data;

		// Размеры графика в px
		$width = isset($options['width'])?$options['width']:500;
		$height = isset($options['height'])?$options['height']:300;
 
		$graph = new Graph($width, $height);
		$graph->SetScale('textlin');

		$lineplots = [];
		if(isset($data[0]) && is_array($data[0])){
			$main = isset($data[0]['data'])?$data[0]['data']:[];
			foreach ($data as $i => $plot) {
				$pdata = isset($plot['data'])?$plot['data']:[];
				$pcolor = isset($plot['color'])?$plot['color']:'blue';
				$pname = isset($plot['name'])?$plot['name']:'';
				$lineplots[$i] = new LinePlot($data[$i]['data']);
				$lineplots[$i]->SetColor($pcolor);
				$lineplots[$i]->SetLegend($pname);
			}
		} else {
			$lineplots[0] = new LinePlot($data);
			$lineplots[0]->SetColor('blue');	
		}

		// Шаг по X от размера массива данных
		$l = sizeof($main);
		$step = $l / 10;
		//$graph->SetScale('intlin',0,0,0,$l);
		//$graph->xscale->SetAutoMin(0);
		$graph->xaxis->SetTextLabelInterval( $step );

		foreach ($lineplots as $lineplot) {
			$graph->Add($lineplot);
		}

		$tmpfname = tempnam(sys_get_temp_dir(), 'NNG');
		$graph->Stroke($tmpfname);

		$img = file_get_contents($tmpfname);
		unlink($tmpfname);

		if($return == 'img'){
			return '<img src="data:image/png;base64,'.base64_encode($img).'" />';
		}

		return $img;
	}

	public function graphEducateErrors($return = 'img', $options = ['width' => 500, 'height' => 300]){
		// Построение графика ошибки обучения
		// При наличии тестовой выборки в процессе обучения, график будет собержать и динамику ошибки на тестовой выборке
		$data = [];
		$test = [];

		if($this->net){
			$results = $this->net->getEducateResults();
			$session = isset($results['session'])?$results['session']:null;
			$errors = isset($results['errors'])?$results['errors']:[];
			$test = isset($results['testErrors'])?$results['testErrors']:null;
			$data = $errors;
		}

		if(!empty($test)){
			$data = [
				[
					'data' => $errors,
					'color' => 'blue',
					'name' => 'dataset'
				],
				[
					'data' => $test,
					'color' => 'red',
					'name' => 'testset'
				]
			];
		}

		return $this->graph($data, $return, $options);
	}

	public static function readDataCSV ($file = '', $options = []){
		// Чтение файла CSV с возвратом массива данных
		if(!mb_strstr($file, '/')){
			$file = __DIR__.'/data/'.$file;
		}else{
			if(!mb_substr($file, 0, 1) != '/'){
				$file = '/'.$file;
			}
			$file = __DIR__.$file;
		}
		if(!$file || !file_exists($file)){
			return null;
		}

		$fp = fopen($file, 'r');
		$i = 0;
		$lineArr = [];

		$allFile = file_get_contents($file);
		if(!$allFile){
			return [];
		}

		// опции
		$convert = isset($options['convert'])?$options['convert']:true;
		$digit = isset($options['digit'])?$options['digit']:true;

		// выделение массива строк
		if($convert){
			$allFile =  @mb_convert_encoding($allFile, 'UTF-8', 'Windows-1251');
		}
		$lines = explode("\n", $allFile);
		if(!$lines || !is_array($lines)){
			return [];
		}

		// выделение колонок в строке и создание общего массива разобранного файла 
		foreach ($lines as $i => $line) {
			$line = str_replace("\r", "", $line);
			$line = str_replace("\n", "", $line);
			if(!$line || $line === ''){
				continue;
			}
			// массив строки
			$lineA = explode(";", $line);

			// избавление от граничных пробелов, кавычек и т.п.
			foreach ($lineA as $j => $value) {
				$lineA[$j] = trim($value);
				$lineA[$j] = str_replace('"""', '"', $lineA[$j]);
				$lineA[$j] = str_replace('""', '"', $lineA[$j]);
				if($lineA[$j] && $lineA[$j][0] == '"'){
					$lineA[$j] = mb_substr($lineA[$j], 1);
				}
				if($digit && ($lineA[$j] || $lineA[$j] == '0')){
					$lineA[$j] = str_replace(',', '.', $lineA[$j]);
					if(is_numeric($lineA[$j])){
						$lineA[$j] = floatval($lineA[$j]);
					}
				}
			}

			$lineArr[] = $lineA;
		}

		// удалим пустые строки
		$result = [];
		foreach ($lineArr as $i => $line) {
			$lineSTR = implode('', $line);
			$lineSTR = trim(str_replace(';', '', $lineSTR));
			if($lineSTR){
				$result[] = $line;
			}
		}

		return $result;
	}

	public function saveEducateErrors($filename = ''){
		// Сохранение динамики изменения ошибки обучения в файл
		// Имя файла (если не указано) = <СЕССИЯ ОБУЧЕНИЯ>_edErrors.csv
		// Директория: <Имя сети> или "nonameNets"
		if(!$this->net || empty($this->net->getEducateResults())){
			return false;
		}
		
		// Параметры обучения - сессия и массив ошибок
		$results = $this->net->getEducateResults();
		$session = isset($results['session'])?$results['session']:null;
		$errors = isset($results['errors'])?$results['errors']:null;
		if(!$errors || empty($errors)){
			return false;
		}

		// Имя файла и директория
		if(!$filename){
			$filename = $session.'_edErrors.csv';
		}
		$filename = $this->getDir().$filename;

		// Формируем содержимое файла
		$content = '';
		foreach ($errors as $era => $err) {
			$content.=$era.';'.$err."\r\n";
		}

		// Сохранение файла
		$r = $this->filewrite($filename, $content);
		if(!$r){
			return $r;
		}

		return $filename;
	}

	public function filewrite($path = '', $content = '', $mode = 'w'){
		$p = explode('/', $path);
		array_splice($p, sizeof($p)-1, 1);
		$dir = implode('/', $p);
		if(!is_dir($dir)){
			$old = umask(0);
			@ $r = mkdir($dir, 0775, true);
			umask($old);
		}

		$file = fopen($path, $mode);
		if(!$file){
			return false;
		}
		fwrite($file, $content);
		fclose($file);

		return true;
	}

	private function getDir(){
		$dir = 'NoNames/';
		
		if($this->net && $this->net->get('name')){
			$dir = $this->net->get('name').'/';
		}
		$dir = __DIR__.'/nets/'.$dir;

		return $dir;
	}

	private static function prettyJSONBlockArr($json, $block){
		$patt='|\"'.$block.'\": \[\n(.*?)\]|si';
		$str = preg_replace_callback($patt, function($m){
			$mm=str_replace("    ", "", $m[1]);
			$mm=str_replace("\n", "", $mm);
			$mm=str_replace("\",\"", "\", \"", $mm);
			$mm='"[!block!]": [ '.$mm.' ]';
			return $mm;
		}, $json);

		$str=str_replace('[!block!]', $block, $str);

		return $str;
	}
}
?>
