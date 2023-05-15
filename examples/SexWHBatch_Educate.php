<?php
require_once '../includer.php';

/* Пример обучения сети для выборки Рост-Вес-Пол. C использование батчей (партий) обучения по 5 записей 
	Определение пола по весу и росту
 */

// Получить данные из файла CSV
$data = NetService::readDataCSV('SexWHData.csv');
if(!$data){
	die('Не удалось получить исходные данные');
}
unset($data[0]);

// Перемешать данные
shuffle($data);

// Разделить обучающий набор и тестовый ($dataSet: 1/3 данных,  $testSet - 1/3)
$l = sizeof($data);
$frontir = intval($l/3) * 2;
$dataSet = [];
$testSet = [];
foreach ($data as $i => $item) {
	if($i < $frontir){
		$dataSet[] = $item;
	}else {
		$testSet[] = $item;
	}

}

// Min/Max по колонкам данных для стандартизации
$scale = (new DataService($dataSet))->getScale();

// Конфигурация сети
$seed = null;
$seed = 514935619; 
$conf = [
	'name' => 'SexWHBatch',
	'speed' => 0.02,				// Скорость градиентного спуска (гиперпараметр)
	'momentum' => 0.75,				// Момент  (гиперпараметр)
	'activation' => 'leakyrelu',	// Функция активации
	'activationByLayers' => [		// На последнем слое - сигмоида для получения 1/0
		2 => 'sigmoid'
	],
	'inputs' => 2,					// Количество входов X сети
	'scaleInputs' => $scale,		// Нормализация данных
	'layers' => [10, 3, 1],			// Архитектура, только скрытые слои
	'bias' => true
];

// Инициализация сети
$net = new FF($conf);
$seed = $net->generateWs('auto', $seed);

// Обучение
$res = $net->educate($dataSet, 3, ['shuffle' => true, 'batch' => 5]);
if($net->isErrors()){
	$net->printErrors();
	die();
}

// Параметры результата обучения
echo 'SEED: '.$seed.'<br>';
echo 'ERROR: '.$res['result']['error'].' ('.$res['result']['errType'].')<br>';
echo 'TRUTH: '.$res['result']['truth'].'<br>';
echo 'TIME: '.$res['result']['time'].'s<br>';

// График изменения ошибки в процессе обучения
$netServ = new NetService($net);
$graf = $netServ->graphEducateErrors();
echo $graf;
echo '<br>';

// Проверка на тестовом множестве
echo '<br><br>TEST DATA<br>';
$res = $net->test($testSet);
if(!$res['success']){
	var_dump($res);
	die();
}
echo 'ERROR: '.$res['err'].' ('.$res['errType'].')<br>';
echo 'MIN: '.$res['min'].'<br>';
echo 'MAX: '.$res['max'].'<br>';

//$net->save();

?>