<?php
/*	FEED FORWARD
	-----------------------------------------
	Copyright © 2023 Unlicense 
	Authors @ Daniel Vasiliev & Vitaly Krilov
	
	Библитека для построения и образования нейросетей Прямого Распространения
	Функции активации: сигмоида, гиперболический тангенс, ReLu, Leaky ReLu, softmax, линейная, пороговая
	Расчет ошибки: MSE, CrossEntropy
	Обучение методом обратного распространения (Backpropagation) с корректировкой весов методом градиентного спуска

	Формат конфигурации сети
		[
			'name' => <string>, 			// Наименование сети, используется для хранения установок и весов
			'speed' => <float>,				// Скорость градиентного спуска (гиперпараметр сети)
			'momentum' => <float>,			// Момент смещения функции вес-ошибка (гиперпараметр сети)
			'activation' => <string>,		// Функция активации: sigmoid (по умолчанию), tangh, relu, softmax, linear, threshold
			'activationByLayers' => [		// Функция активации для слоя, layerIndex - индекс слоя
				<layerIndex> => <string>
			]
			'inputs' => <int>,				// Количество входных нейронов
			'scaleInputs' => 				// "Стандартизация" входных значений:
				true/false 					// 		Vi = 1/log(Vi);
				[i => [min, max], ...],		// 		Vi = (Vi - Vmin) / (Vmax - Vmin); 
											// ВАЖНО! Min/Max для каждого индекса коронки обучающей выборки данных необходимо сохранять и потом назначать для сети в процессе её эксплуатации
			'layers' => [<int>, ...],		// Массив СКРЫТЫХ слоев с указанием количества нейронов в каждом
			'bias' =>  true/false/			// Наличие нейронов смещения (BIAS) на всех слоях сети
						'onlyLast',			//		onlyLast - добавлять BIAS только на последнем скрытом слое (к выходному)
			'biasInput' => true/false, 		// Автоматически дополнять входные данные единицей: input = [1.1, 1.2, 1.3] => [1.1, 1.2, 1.3, 1]
											//	Если не указано иное, включается при bias = true
			'regular' => <float>			// Регуляризация оценки ошибки, E = E + L * SUM (|Wi|), задается величина L
		]

	Формат массива весов связей сети
		$ws = [массив весов [массив слоёв [массив входных связей каждого нейрона], ...], ...]
		Пример для сети inputs = 3, layers = [2, 1]:
		$ws = [
			[[0.45, -0.12, 0.4], [0.78, 0.13, 0.84]],	// первый слой - N элементов = N нейронов во первом слое, Ni элементов = количество входных X
			[[1.5, -2.3]]								// второй слой - N элементов = N нейронов во втором слое, Ni элементов = количество нейронов на первом слое
		];

	Обращение к сети c заданными весами, пример
		$net = new FF($conf);
		$net->setWs($ws);
		$res = $net->predict([1, 0]);
		$res = ['success' => true/false, 'result' => [1]]

	Функции обучения сети
		forward ($input, $output, $validate) - прямое прохождение сети с расчетом ошибки при наличии эталона ($output)
		back($output, $validate, $options) - обратное прохождение сети (backpropagation) с корректировкой весов
		predict ($input, $validate) - прямое прохождение сети, алиас к forward без учета эталонного ответа, без расчета ошибки
		learn ($input, $output) - одна итерация обучения, использующая forward + back
		educate ($dataset, $eraN, $options, $testset) - полный курс обучения сети на обучающем DATA сете
		test ($testset, $options) - проверка сети на тестовом множестве, с фиксацией ошибки (минимальной, средней, максимальной)
	
	Функция educate($dataset, $eraN, $options, $testset) - полный курс обучения.
		$dataset <array> - обучающий индексированный двухмерный массив [i-строка, j-колонка],
		$eraN <int> - количество эпох обучения
		$options <array> - опции:
			- 'outputs' <array> ([j1, j2, ...]) - индексы колонок-эталонов в $dataset, если не заданы, то используются последние N-колонок, где N = число нейронов выходного слоя
			- 'shuffle' <bool> - случайным образом перемешивать $dataset перед каждой эпохой
			- 'batch' <int> - размер батча (порции) корректировки весов, по умолчанию обучение проходит без батчей с корректировкой весов на каждой итерации внутри эпохи
			- 'untilError' - величина ошибки на которой обучение останавливается автоматически, по умолчанию не используется ( = 0)
		$testset <array> - тестовая выборка, если не пустая, то для каждой эпохи и/или батча фиксируется ошибка по тестовой выборке
	
	Инициация (генерация) весов
		generateWs($type = 'auto', $seed = null, $params = []) = seed
		 	$type - метод случайной генерации: auto, uniform, normal, havier, glorot, he, kayming (подробнее о методах в классе Rand)
				auto - функция самостоятельно определяет наилучший метод для каждого слоя
			$params - атрибуты генерации для uniform и normal. 
				Для uniform это диапазон равномерного распределения
				Для normal - мат. ожидание и отклонение от нормали

	Пример обучения XOR на примитивах класса (не рекомендуется, обучать сеть лучше с помощью функции educate() по обучающему множеству dataSet)
		$conf = [
			'name' => 'XOR',
			'speed' => 1,
			'momentum' => 0.9,
			'activation' => 'sigmoid',
			'inputs' => 2,
			'layers' => [4, 2, 1]
		];
		$data = [
			['input' => [1, 0], 'output' => [1]],
			['input' => [1, 1], 'output' => [0]],
			['input' => [0, 1], 'output' => [1]],
			['input' => [0, 0], 'output' => [0]]
		];

		$net = new FF($conf);
		$seed = $net->generateWs();

		$errors = [];
		for ($i = 0; $i < 1000; $i++) { 
			$etalons = [];
			$results = [];
			foreach ($data as $item) {
				$etalons[] = $item['output'][0];
				$res = $net->learn($item['input'], $item['output']);
				foreach ($res['result'] as $value) {
					$results[] = $value;
				}
			}
			$errors[] = $net->MSE($results, $etalons);
		}
		$err = $errors[$i-1];

		$netServ = new NetService($net);
		$graf = $netServ->graph($errors);
		echo $graf;
		echo '<br>';

		echo 'ERROR = '.$err.'<br>';
		echo 'SEED = '.$seed.'<br>';
		echo '<br>';

	Примеры успешного обучения сетей на educate()
		examples/*.php

	Вывод информации о сети (архитектура и состояние)
		$net->printNet($short <bool>);
			- short - короткая форма, сокращает значения весов до 4-х знаков, не выводит значение на нейронах и ошибку обучения на нейронах

	График изменения ошибки в процессе обучения (используется библиотека JPGraph)
		$netServ = new NetService($net);
		...
		$net->educate($dataSet);
		...
		$graf = $netServ->graphEducateErrors();
		echo $graf;

		или по заданному массиву ошибок:
		$netServ = new NetService();
		$graf = $netServ->graph($errors);
 
	Сохранение сети в файлы
		Проинициализированную сеть можно сохранить функцией
			$net->save()
		Сохранение обуспечивает класс NetService
		Сеть сохраняется двумя файлами в директорию 
			<current>/nets/<net name>/
		Файлы
			config.json - конфигурация сети
			weights.json - весовые коэффициенты сети

	Эксплуатация обученной и сохраненной сети
		Самая короткая форма:
			$res = (new FF(<net name>))->predict([<data>]);
		Пример:
			$res = (new FF('XOR_Sigmoid'))->predict([1, 0]);
			echo '1 xor 0 = '.round($res['result'][0]);
 */

class FF {

	private $name = null;
	private $input = [];
	private $output = [];
	private $net = [];
	private $ws = [];
	private $fnc = 'sigmoid'; //sigmoid, tangh, relu, leakyrelu, softmax, linear, threshold
	private $fncL = [];
	private $layers = [1, 1];
	private $inputs = 1;
	private $scaleInputs = false;
	private $bias = false;
	private $biasInput = null;
	private $E = 0.1;
	private $m = 0;
	private $L = 0;
	private $result = ['success' => false, 'result' => null, 'error' => ''];
	private $errors = [];
	private $vs = [];
	private $educateSession = null;
	private $educateErrors = [];
	private $testErrors = [];

	public function __construct($cfg = []) {
		if($cfg && is_array($cfg)){
			$this->setNet($cfg);
			$this->initNet();
		}
		else if($cfg && is_string($cfg)){
			$this->name = $cfg;
			$this->read();
		}

		return $this;
	}

	// Конфигурация сети
	public function setNet($cfg = []){
		// Разбор и применение конфигурации сети по массиву $cfg
		if(!is_array($cfg)){
			return;
		}

		if(isset($cfg['name']))					$this->name = $cfg['name'];
		if(isset($cfg['speed']))				$this->E = $cfg['speed'];
		if(isset($cfg['momentum']))				$this->m = $cfg['momentum'];
		if(isset($cfg['regular']))				$this->L = $cfg['regular'];
		if(isset($cfg['activation']))			$this->fnc = $cfg['activation'];
		if(isset($cfg['activationByLayers']))	$this->fncL = $cfg['activationByLayers'];
		if(isset($cfg['inputs']))				$this->inputs = $cfg['inputs'];
		if(isset($cfg['scaleInputs']))			$this->scaleInputs = $cfg['scaleInputs'];
		if(isset($cfg['layers']))				$this->layers = $cfg['layers'];
		if(isset($cfg['bias']))					$this->bias = $cfg['bias'];
		if(isset($cfg['biasInput']))			$this->biasInput = $cfg['biasInput'];

		if($this->bias && $this->biasInput !== false){
			$this->biasInput = true;
		}
		if($this->bias == 'onlyLast'){
			$this->biasInput = false;	
		}
		
		return $this;
	}

	public function getNet(){
		// получение полной конфигурации сети
		return [
			'name' => $this->name,
			'speed' => $this->E,
			'momentum' => $this->m,
			'regular' => $this->L,
			'activation' => $this->fnc,
			'activationByLayers' => $this->fncL,
			'inputs' => $this->inputs,
			'scaleInputs' => $this->scaleInputs,
			'layers' => $this->layers,
			'bias' => $this->bias,
			'biasInput' => $this->biasInput
		];
	}

	public function get($param = null){
		// получение параметра конфигурации сети
		$cfg = $this->getNet();

		if(!$param){
			return $cfg;
		}

		$attr = isset($cfg[$param])?$cfg[$param]: null;
		return $attr;
	}

	public function set($param = '', $value = null){
		// установка одного параметра конфигурации сети
		if(!$param || !is_string($param)){
			return false;
		}

		$microCfg = [];
		$microCfg[$param] = $value;
		$this->setNet($microCfg);

		return $this->get($param);
	}

	public function setWs($ws = []){
		// Установка весовых коэффициентов сети извне
		// $ws = [массив весов [массив слоёв [массив входных связей каждого нейрона], ...], ...]
		if(!$this->net || empty($this->net) || !$ws || empty($ws)){
			$this->setError('Не заданы весовые коэффициенты сети или не инициализирована сеть');
			return null;
		}

		$this->ws = $ws;

		// Слои
		foreach ($this->net as $layer => $neurons) {
			if(!is_array($neurons) || empty($neurons)){
				$this->setError('Не инициализированы нейроны для слоя '.$layer);
				continue;
			}
			// Нейроны слоя
			foreach ($neurons as $neuron) {
				if(!$neuron->incomes || !is_array($neuron->incomes)){
					$this->setError('Не инициализированы связи для (слой-нейрон):'.$layer.'-'.$neuron->index);
					continue;
				}
				// Входящие в нейрон связи
				foreach ($neuron->incomes as $i => $link) {
					$w = $this->getWByArr($ws, $layer, $neuron->index, $i);
					if(is_null($w) && $neuron->type != 'bias'){
						$this->setError('Не задана связь (слой-нейрон-связь): '.$layer.'-'.$neuron->index.'-'.$i);
						$w = 0;
					}

				 	$link->setW($w);
				} 
			}
		}

		return $this;
	}

	public function generateWs($type = 'auto', $seed = null, $params = []){
		// Генерация случайных весов связей
		// Допустимые типы: auto, uniform, normal, havier, glorot, he, kayming (подробнее о методах в классе Rand)
		// $type = 'auto' - функция самостоятельно определяет наилучший метод для каждого слоя
		// $params - атрибуты генерации для uniform и normal
		// Возвращает SEED, который был использован для генерации
		if(!$this->net || empty($this->net)){
			$this->setError('Сеть не инициализирована!');
			return null;
		}

		$ws = [];
		$layersN = sizeof($this->layers);

		// Параметры генерации случайных весов
		$rand = new Rand($seed);
		$seed = $rand->getSeed();
		if($type != 'auto' && !method_exists($rand, $type)){
			$type = 'auto';
		}

		// Обработка сети
		foreach ($this->net as $layer => $neurons) {
			$nn = sizeof($neurons);
			$fnc = $this->getLayerFnc($layer);

			// Функция генерации весов по слою
			$randFnc = $type;
			if($type == 'auto'){
				// Для ReLu - по методу Кайминга Хе 
				if($fnc == 'relu' || $fnc == 'leakyrelu'){
					$randFnc = 'he';
				}
				// Для сигмоид - по методу Ксавьера/Глорота
				else if($fnc == 'sigmoid' || $fnc == 'tangh'){
					$randFnc = 'havier';
				}
				// В остальных случаях: нормальное распределение случайного числа
				else{
					$randFnc = 'normal';
				}
			}

			// Нейроны слоя
			foreach ($neurons as $neuron) {
				// Аргументы функции генерации
				$args = $params;
				if($randFnc == 'he' || $randFnc == 'kayming' || $randFnc == 'havier' || $randFnc == 'glorot'){
					$args = [sizeof($neuron->incomes)];
				}

				// входные только для первого слоя
				if($layer === 0){
					foreach ($neuron->incomes as $link) {
						if($neuron->type == 'bias'){
							$w = 0;
						}else{
							$w = call_user_func_array([$rand, $randFnc], $args);
						}
						$link->setW($w);
					}
				}
				// выходные - для всех, кроме последнего слоя
				if($layer < ($layersN - 1)){
					foreach ($neuron->outs as $link) {
						if($neuron->type == 'bias'){
							$w = 0;
						}else{
							$w = call_user_func_array([$rand, $randFnc], $args);
						}
						$link->setW($w);
					}
				}
			}
		}

		$this->getWs();

		return $seed;
	}

	public function getWs(){
		// Сбор массива весов связей с объекта сети
		$this->ws = [];

		foreach ($this->net as $layer => $neurons) {
			$wsL = [];

			foreach ($neurons as $neuron) {
				$wsN = [];

				foreach ($neuron->incomes as $i => $link) {
					$wsN[] = $link->getW();
				}

				$wsL[] = $wsN;
			}

			$this->ws[] = $wsL;
		}

		return $this->ws;
	}

	public function setData($input = [], $output = []){
		// Установка данных: $input - массив входных данных, $output - массив эталонных значений на выходе сети (для обучения)
		$this->input = $input;
		$this->output = $output;

		$this->setInputs($input);

		return $this;
	}

	public function initNet($config = []){
		// Инициализация объектной модели сети
		if(!empty($config)){
			$this->setNet($config);
		}
		if(!$this->layers || !is_array($this->layers)){
			$this->setError('Не задана архитектура сети "layers" (массив количества нейронов по слоям)');
			return false;
		}
		if(!$this->inputs){
			$this->setError('Не задано количество значений X на входе в сеть (inputs)');
			return false;
		}

		$this->net = [];
		$layersN = sizeof($this->layers);

		$prewN = $this->inputs;

		foreach ($this->layers as $layer => $nNeurons) {
			$this->net[$layer] = [];

			// Добавляется ли BIAS (нейрон смещения) на слое (кроме выходного)
			// С опцией $this->bias == 'onlyLast' биас добавляется только на последнем скрытом слое
			$bias = false;
			if($this->bias && $layer != ($layersN - 1)){
				if(($this->bias == 'onlyLast' && $layer == ($layersN - 2)) || $this->bias != 'onlyLast'){
					$nNeurons++;
					$bias = true;
				}
			}

			for ($i = 0; $i < $nNeurons; $i++) { 
				$type = 'plane';
				if($bias && $i == ($nNeurons - 1)){
					$type = 'bias';
				}

				$neuron = new Neuron($layer, $i, $type);

				// Входные связи к нейрону
				for ($j = 0; $j < $prewN; $j++) { 
					$A = 'x';
					if($layer > 0){
						$A = $this->net[$layer - 1][$j];
					}

					$link = new Link($A, $neuron, $layer);
				}

				$this->net[$layer][] = $neuron;
			}

			$prewN = $nNeurons;
		}
	}

	public function setInputs($input = []){
		// Установка входных параметров (x)
		if(!$this->net || empty($this->net)){
			$this->setError('Сеть не инициализирована!');
			return null;
		}
		if(empty($input) && $this->input){
			$input = $this->input;
		}
		if(!$input || !is_array($input) || empty($input)){
			$this->setError('Не указаны входные параметры');
			return null;	
		}

		// Стандартизация - логорифмическая (X = 1/log(X)) либо нормализация (X = (X - Xmin) / (Xmax - Xmin))
		foreach ($input as $i => $v) {
			if($this->scaleInputs === true){
				$input[$i] = Math::fnc('scaleLog', [$v]);
			}
			else if(isset($this->scaleInputs[$i])){
				$min = isset($this->scaleInputs[$i]['min'])?$this->scaleInputs[$i]['min']:null;
				$max = isset($this->scaleInputs[$i]['max'])?$this->scaleInputs[$i]['max']:null;
				if(!is_null($min) && !is_null($max)){
					$input[$i] = Math::fnc('scaleMinMax', [$v, $min, $max]);
				}
			}
		}

		// Автоколонка данных = 1 (BIAS входных данных)
		if($this->biasInput){
			$input[] = 1;
		}

		$this->input = $input;

		// Нулевой слой нейронов
		$neuronsX = $this->net[0];

		// Для каждого нейрона 0-го уровня, по всем входным связям - назначаем переданные данные
		foreach ($neuronsX as $neuron) {
			$linksX = $neuron->incomes;
			foreach ($linksX as $i => $link) {
				$x = isset($this->input[$i])?$this->input[$i]:0;
				$link->setA($x);
			}
		}
	}

	public function getOutputIndexes(&$dataset = [], $outputs = null){
		// Получение индексов эталонных Y для выборки данных
		if(empty($this->layers) || empty($dataset)){
			return $outputs;
		}

		// Индексы колонок-эталонов обучения по количеству нейронов в последнем слое
		if(!$outputs || empty($outputs)){
			$nLastLayer = $this->layers[sizeof($this->layers) - 1];
			$dataX = sizeof($dataset[0]);
			$outputs = [];
			for ($i = 0; $i < $nLastLayer; $i++) { 
				$outputs[] = $dataX - ($i + 1);
			}
		}

		return $outputs;
	}

	public function printNet($short = true){
		// Вывод параметров сети в HTTP-поток
		if(!$this->net || empty($this->net)){
			echo 'Net is not init';
			return;
		}

		echo 'Inputs: '.$this->inputs.'<br>';
		echo 'Input values: '.implode('; ', $this->input).'<br><br>';
		

		foreach ($this->net as $layer => $neurons) {
			echo 'Layer '.$layer.'<br>-------<br>';
			
			if(!is_array($neurons) || empty($neurons)){
				continue;
			}

			foreach ($neurons as $neuron) {
				$name = ($neuron->type == 'bias')?'BIAS':'Neuron';
				echo $name.' #'.$neuron->index.' ';

				if(!$short){
					echo 'v = '.$neuron->get();
					if($neuron->dE){
						echo ' dE = '.$neuron->dE;
					}
				}
				echo ' | ';

				if($neuron->incomes && is_array($neuron->incomes)){
					echo 'Income Links: ';
					foreach ($neuron->incomes as $i => $link) {
						$w = $link->getW();
						if($short && $w){
							$w = round($w, 4);
						}
						echo $w;
						if(!$short) echo '(v = '.$link->getX(). ')';
						echo '; ';
					}
				}

				echo '<br>';
			}
		}
	}

	// Предсказание и обучение
	public function forward($input = null, $output = null, $validate = true){
		// Прямой проход по нейросети
		if(!is_null($input)){
			$this->setInputs($input);
		}

		// Валидация состояния сети
		if($validate){
			if(!$this->validateNet() || $this->isErrors()){
				return $this->result;
			}
		}

		$this->vs = []; // результат: массив значений на выходном слое
		$layersN = sizeof($this->layers);

		// Цикл по слоям
		$fnc = $this->fnc;
		foreach ($this->net as $layer => $neurons) {
			// Функция активации на слое
			$fnc = $this->getLayerFnc($layer);

			// Принцип активации по слою: для каждого нейрона или вектором по всему слою
			$activate = 'every';
			if($fnc == 'softmax'){
				$activate = 'layer';
			}

			// Цикл по нейронам слоя
			$vs = [];
			foreach ($neurons as $neuron) {
				$v = $neuron->getSum();
				if($activate == 'every'){
					$v = Math::fnc($fnc, [$v]);
				}
				if(is_nan($v)) $v = 0;
				if(is_infinite($v)) $v = PHP_INT_MAX;
				
				$neuron->set($v);

				$vs[] = $v;
			}

			// Активация по всему слою сразу
			if($activate == 'layer'){
				$vs = Math::fnc($fnc, [$vs]);
				foreach ($neurons as $i => $neuron) {
					$neuron->set($vs[$i]);
				}
			}

			// Из последнего слоя собираем конечный результат
			if($layer == ($layersN - 1)){
				$this->vs = $vs;
			}
		}

		// Ошибка по итерации
		$options = [];
		if($output){
			if($fnc == 'softmax'){
				$options['err'] = Math::fnc('crossEntropy', [$this->vs, $output]);
				$options['errType'] = 'crossEntropy';
			}else{
				$options['err'] = Math::fnc('MSE', [$this->vs, $output]);
				$options['errType'] = 'MSE';
			}
		}

		// Результат
		$this->setSuccess($this->vs, $options);

		return $this->result;
	}

	public function predict($input = null, $validate = true){
		return $this->forward($input, null, $validate);
	}

	public function back($output = [], $validate = true, $options = []){
		// Backpropagation корректировка весов, использует $this->output - эталон, $this->vs - фактический результат прямого распространения
		if(!empty($output)){
			$this->output = $output;
		}
		if($validate){
			if(empty($this->vs)) {
				$this->setError('Не расчитано выходное значение сети (predict())');
				return $this->result;
			}
			if(empty($this->output)) {
				$this->setError('Не заданы эталонные значения');
				return $this->result;
			}
			if(sizeof($this->vs) != sizeof($this->output)){
				$this->setError('Количество эталонных значений не соответсвует количеству выходных значений сети');
				return $this->result;
			}
		}

		// Опции
		$byBatch = isset($options['byBatch'])?$options['byBatch']:false;

		// Цикл от последнего слоя к первому
		$layersN = sizeof($this->layers);
		for ($i = $layersN - 1; $i >= 0; $i--) { 
			// Функция активация слоя
			$fnc = $this->getLayerFnc($i);

			// Нейроны слоя
			$neurons = $this->net[$i];

			// Цикл по нейронам слоя
			foreach ($neurons as $ni => $neuron) {
				$v = $neuron->get(); // Значение на нейроне

				// Для последнего слоя (выходного) только считаем дельты ошибки
				// dE = (etalon - value) * dF, где dF - производная функции активации
				// Если вызов из процесса обучения батчами, то ошибку нужно суммировать с имеющейся на нейроне
				if($i == ($layersN - 1)){
					$eta = $this->output[$ni];
					$dE = ($eta - $v) * Math::fnc($fnc, [$v, true]);
					if($byBatch){
						$dE = $dE + $neuron->dE;
					}
					$neuron->setError($dE, $this->L);
				}
				// Для остальных слоев дельта ошибки = SUM(Wi*dE) * dF, где 
				//		Wi - веса выходных связей текушем нейрона, 
				//		dE - дельта ошибки на другом конце связи (на нейроне следующего по глубине слоя, с которым связан текущий), 
				//		dF - производная функции активации
				// Далее вычисляет Градиент (G) и dW - корректировки весов (исходящих), dW фиксируется на связи
				// Расчет с учетом шага градиентного спуска (скорости) E и смещения функции (m) 
				// 		G = dE * V, ошибка на узле на значение узла
				// 		dW = E*G + m * dWi-1, где dWi-1 - вес связи зафиксированный на предыдущей итерации, 
				// Новый вес W = Wold + dW
				else{
					$sumDW = 0;

					// Цикл по выходным связям нейрона, получение суммы SUM(Wi*dE)
					foreach ($neuron->outs as $li => $link) {
						if(($link->B instanceof Neuron) && $link->B->type == 'bias'){
							continue;
						}

						$dEw = $link->B->dE;
						$w = $link->getW();
						$sumDW = $sumDW + $w * $dEw;

						// Градиент от dE на нейроне В связи
						$G = $dEw * $v;

						// величина корректировки веса связи
						$dW = $this->E * $G + $this->m * $link->getDW();
						$link->correctW($dW);
					}

					// дельта ошибки на нейроне
					$dE = $sumDW * Math::fnc($fnc, [$v, true]);
					$neuron->setError($dE);

					// Для первого слоя - корректировка входных весов (т.к. внешнего слоя у нас фактически нет в объектах)
					if($i == 0){
						foreach ($neuron->incomes as $li => $link) {
							if($neuron->type == 'bias') continue;
							$v1 = $link->getX();
							$dEw = $link->B->dE;
							$G = $dEw * $v1;
							$dW = $this->E * $G + $this->m * $link->getDW();
							$link->correctW($dW);
						}
					}
				}
			}
		}
	}

	public function fixPredictE($output = []){
		// Расчет, фиксация и возврат ошибки предсказания на последнем слое сети
		if(!empty($output)){
			$this->output = $output;
		}
		$layersN = sizeof($this->layers);
		$dEs = [];

		// Нейроны последнего слоя
		$neurons = $this->net[$layersN - 1];

		// Функция активация слоя
		$fnc = $this->getLayerFnc($layersN - 1);

		// Цикл по нейронам слоя
		foreach ($neurons as $ni => $neuron) {
			$v = $neuron->get(); // Значение на нейроне

			// dE = (etalon - value) * dF, где dF - производная функции активации
			$eta = $this->output[$ni];
			$dE = ($eta - $v) * Math::fnc($fnc, [$v, true]);

			// Суммируем ошибку к текущей на нейроне
			$neuron->setError($neuron->dE + $dE);

			$dEs[] = $dE;
		}

		return $dEs;
	}

	public function unsetPredictE(){
		// Сброс ошибок на нейронах последнего слоя
		$layersN = sizeof($this->layers);

		// Нейроны последнего слоя
		$neurons = $this->net[$layersN - 1];

		// Цикл по нейронам слоя
		foreach ($neurons as $ni => $neuron) {
			$neuron->setError();
		}
	}

	public function learn($input = [], $output = []){
		// Обучение, 1 итерация внутри эпохи - сводная функция по predict() и back()
		if(empty($input) || empty($output)){
			$this->setError('Не заданы массивы исходных и/или эталонных данных для обучения');
			return $this->result;
		}

		$this->predict($input, false);
		if(!$this->isErrors()){
			$this->back($output);
		}

		return $this->result;
	}

	public function educate($dataset = [], $eraN = 1000, $options = [], $testset = []){
		// Полный курс обучения.
		/**
			$dataset <array> - обучающий индексированный двухмерный массив [i-строка, j-колонка],
			$eraN <int> - количество эпох обучения
			$options <array> - опции:
				- 'outputs' <array> ([j1, j2, ...]) - индексы колонок-эталонов в $dataset, если не заданы, то используются последние N-колонок, где N = число нейронов выходного слоя
				- 'shuffle' <bool> - случайным образом перемешивать $dataset перед каждой эпохой
				- 'batch' <int> - размер батча (порции) корректировки весов, по умолчанию обучение проходит без батчей с корректировкой весов на каждой итерации внутри эпохи
				- 'untilError' - величина ошибки на которой обучение останавливается автоматически, по умолчанию не используется ( = 0)
			$testset <array> - тестовая выборка, если не пустая, то для каждой эпохи и/или батча фиксируется ошибка по тестовой выборке
		 */

		// Время начала
		$t1 = microtime(true);

		// Генерация весов
		$seed = null;
		if(empty($this->ws) || isset($options['rand'])){
			$seed = $this->generateWs();
		}

		// Валидация сети
		if(!$this->validateNet(['emptyInput' => true]) || $this->isErrors()){
			return $this->result;
		}

		// Опции
		$shuffle = isset($options['shuffle'])?$options['shuffle']:false;
		$outputs = isset($options['outputs'])?$options['outputs']:null;
		$batch = isset($options['batch'])?$options['batch']:null;
		$untilError = isset($options['untilError'])?$options['untilError']:0;

		// Батчи - если указаны как булевая истина, то размер = 100 записей
		if($batch && !is_integer($batch)){
			$batch = 100;
		}

		// Индексы колонок-эталонов обучения
		$outputs = $this->getOutputIndexes($dataset, $outputs);

		// Сессия и ошибки обучения
		$this->educateSession = mt_rand(0, mt_getrandmax());
		$this->educateErrors = [];
		$this->testErrors = [];

		// Тип подсчета ошибки от функции активации на последнем слое
		$errType = $this->getErrType();

		// Обучение
		$end = false;
		$datasetN = sizeof($dataset);
		for ($era = 0; $era < $eraN; $era++) {
			// Параметры для последующего вычисления ошибки MSE для Эры
			$error = 1;
			$vs = [];
			$as = [];

			foreach ($dataset as $i => $line) {
				// Выделение входных X для сети и эталонных Y
				$input = [];
				$output = [];
				foreach ($line as $j => $v) {
					if(in_array($j, $outputs)){
						$output[] = $v;
					}
					else{
						$input[] = $v;
					}
				}

				// Итерация обучения по батчу
				$endBatch = false;
				if($batch){
					$res = null;
					$this->predict($input, false);
					if(!$this->isErrors()){
						// Тело батча
						if((!$i || $i % $batch) && $i < ($datasetN - 1)){
							$this->fixPredictE($output);
						}
						// Конец батча
						else{
							// Обратное распространение ошибки
							$this->back($output, false, ['byBatch' => true]);

							// Сбросить ошибки на последнем слое
							$this->unsetPredictE();

							$endBatch = true;
						}
						$res = $this->result;
					}
				}
				// Итерация обучения по эпохе
				else{
					$res = $this->learn($input, $output);
				}

				// Фиксируем полученные и эталонные значения для рачета ошибки
				if($res && $res['success']){
					foreach ($res['result'] as $vi => $v) {
						$vs[] = $v;
						$as[] = $output[$vi];
					}

					// Для окончания батча фиксируем ошибку по батчу
					if($endBatch && $i < ($datasetN - 1)){
						$error = Math::fnc($errType, [$vs, $as]);
						$this->educateErrors[] = $error;

						// Ошибка по тестовому датасету, если он передан
						if(!empty($testset)){
							$test = $this->test($testset, ['outputs' => $outputs]);
							if($test && $test['success']){
								$this->testErrors[] = $test['err'];
							}
						} 
					}
				}
			}

			// Расчет ошибки
			$error = Math::fnc($errType, [$vs, $as]);
			if($error < $untilError){
				$end = true;
				$era = $eraN;
			}

			// Ошибка по тестовому датасету, если он передан
			if(!empty($testset)){
				$test = $this->test($testset, ['outputs' => $outputs]);
				if($test && $test['success']){
					$this->testErrors[] = $test['err'];
				}
			} 

			// Перемешиваем выборку перед следующей эпохой
			if($shuffle && !$end){
				shuffle($dataset);
			}

			// Динамика ошибки в процессе обучения
			$this->educateErrors[] = $error;
		}
		if(!$res['success']){
			return $this->result;
		}

		// Показатель ошибки
		$truth = 1 - $error;

		// Время выполнения
		$t2 = microtime(true);
		$t = $t2 - $t1;

		// Наполняем итог обучения
		$this->setSuccess(['eras' => $era, 'error' => $error, 'errType' => $errType, 'truth' => $truth, 'seed' => $seed, 'session' => $this->educateSession, 'time' => $t]);

		return $this->result;
	}

	public function test($testset = [], $options = []){
		// Проверка на тестовом множестве данных с верными ответами в них
		// $testset должен быть полностью аналогичен $dataset для educate()

		// Индексы колонок-эталонов обучения
		$outputs = isset($options['outputs'])?$options['outputs']:null;
		$outputs = $this->getOutputIndexes($testset, $outputs);

		// Цикл проверки
		$error = 0;
		$errors = [];
		foreach ($testset as $i => $line) {
			// Выделение входных X для сети и эталонных Y
			$input = [];
			$output = [];
			foreach ($line as $j => $v) {
				if(in_array($j, $outputs)){
					$output[] = $v;
				}
				else{
					$input[] = $v;
				}
			}

			// Итерация проверки
			$res = $this->forward($input, $output);
			if(!$res['success']){
				return $this->getResult();
			}

			$err = $res['err'];
			$errors[] = $err;
			$error+= $err;
		}

		$error = $error / sizeof($testset);

		$result = ['success' => true, 'err' => $error, 'min' => min($errors), 'max' => max($errors), 'errType' => $this->getErrType()];

		return $result;
	}

	public function getErrType() {
		// Метод вычисления ошибки по функции последнего слоя
		// crossEntropy для softmax, MSE - для остальных
		$errType = 'MSE';
		if(empty($this->layers)){
			return $errType;
		}

		$layersN = sizeof($this->layers);
		$fnc = $this->getLayerFnc($layersN - 1);
		if($fnc == 'softmax'){
			$errType = 'crossEntropy';
		}

		return $errType;
	}

	public function getLayerFnc($layer = 0){
		// получение функции на слое по его индексу
		$fnc = $this->fnc;
		if(isset($this->fncL[$layer]) && $this->fncL[$layer] && Math::fncExist($this->fncL[$layer])){
			$fnc = $this->fncL[$layer];
		}

		return $fnc;
	}

	public function validateNet($options = []){
		// Проверка корректной инициализации сети
		if(!$this->layers || empty($this->layers)){
			$this->setError('Не заданы параметры сети');
			return false;
		}
		if(!$this->ws || empty($this->ws)){
			$this->setError('Не заданы весовые коэффициенты сети');
			return false;
		}
		if(!Math::fncExist($this->fnc)){
			$this->setError('Задана недопустимая функция активации: '.$this->fnc);
			return false;
		}
		if(!sizeof($this->layers)){
			$this->setError('Задана недопустимая архитекрура сети, отсутствуют слои нейронов');
			return false;
		}

		$emptyInput = isset($options['emptyInput'])?$options['emptyInput']:false;
		if(!$emptyInput && !$this->input){
			$this->setError('Не заданы входные данные');
			return false;
		}

		return true;
	}

	// Обслуживание результата обучения и конфигурации сети
	public function getEducateResults(){
		if(!$this->educateSession){
			return [];
		}

		return [
			'session' => $this->educateSession,
			'errors' => $this->educateErrors,
			'testErrors' => $this->testErrors
		];
	}

	// Обслуживание ошибок и результата работы класса
	public function isErrors(){
		return !empty($this->errors);
	}

	public function printErrors(){
		if(empty($this->errors)){
			echo 'no errors';
			return;
		}
		echo implode('; ', $this->errors);
	}

	public function setError($error = 'Ошибка...'){
		$this->errors[] = $error;
		$this->result = ['success' => false, 'errors' => $this->errors];
	}

	public function setSuccess($result = null, $options = []){
		$this->errors = [];
		$this->result = ['success' => true, 'result' => $result];

		if(!empty($options)){
			foreach ($options as $key => $value) {
				if($key != 'success' && $key != 'result'){
					$this->result[$key] = $value;
				}
			}
		}
	}

	public function getResult(){
		return $this->result;
	}

	// Хранение/Чтение сети
	public function save(){
		$service = new NetService($this);
		$net = $service->saveNet();

		$this->getWs();
		if(!empty($this->ws)){
			$ws = $service->saveWs();
		}

		return ['net' => $net, 'ws' => $ws];
	}

	public function read(){
		if(!$this->name){
			return null;
		}

		$service = new NetService($this);
		$cfg = $service->read('config');

		if($cfg){
			$this->setNet($cfg);
			$this->initNet();

			$ws = $service->read('weights');
			if($ws){
				$this->setWs($ws);
			}
		}

		return $cfg;
	}

	// Утилиты класса
	public function MSE ($values = [], $etalons = []) {
		// Обращение к классу Math для расчета среднеквадратичной ошибки
		// values = массив фактических значений НС, etalons = массив ожидаемых (верных) значений
		if(is_scalar($values)) $values = [$values];
		if(is_scalar($etalons)) $etalons = [$etalons];

		return Math::fnc('MSE', [$values, $etalons]);
	}

	public function crossEntropy ($values = [], $etalons = []) {
		// Обращение к классу Math для расчета перекрестной энтропии
		// values = массив фактических значений НС, etalons = массив ожидаемых (верных) значений
		if(is_scalar($values)) $values = [$values];
		if(is_scalar($etalons)) $etalons = [$etalons];
		
		return Math::fnc('crossEntropy', [$values, $etalons]);
	}

	public function getWByArr($ws = [], $layer = 0, $neuron = 0, $i = 0){
		// Получение веса из массива формата [массив весов = [массив слоёв = [массив входных связей каждого нейрона], ...], ...]
		// $layer - индекс слоя, $neuron - индекс нейрона, $i - индекс связи нейрона
		if(!isset($ws[$layer]) || !isset($ws[$layer][$neuron]) || !isset($ws[$layer][$neuron][$i])){
			return null;
		}
		return $ws[$layer][$neuron][$i];
	}
}
?>