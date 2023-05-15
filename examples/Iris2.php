<?php
require_once '../includer.php';

/* Пример обучения сети для выборки Ирисов Фишера с графиком динамики ошибки по тестовой выборке
	Определение сорта ириса по размерам частей его цветка
	Исходные данные для обучения и анализа взяты с https://github.com/geetharamson/Fisher-s-Iris-Dataset/blob/master/iris.csv
	Подготовленные данные располагаются в директории <root>/data/IrisData.csv
		Для подготовки следует заменить колонку с именем сорта Ириса на три колонки классов: 1-0-0 для первого сорта Ириса, 0-1-0 для второго, 0-0-1 - для третьего
 */

// Получить данные из файла CSV
$data = NetService::readDataCSV('IrisData.csv');
if(!$data){
	die('Не удалось получить исходные данные по Ирисам');
}
unset($data[0]);

// Перемешать данные
shuffle($data);

// Разделить обучающий набор и тестовый ($dataSet: 2/3 данных,  $testSet - 1/3)
$l = sizeof($data);
$frontir = intval($l/3) * 2;
$dataSet = [];
$testSet = [];
foreach ($data as $i => $item) {
	if($i < $frontir){
		$dataSet[] = $item;
	}else{
		$testSet[] = $item;
	}
}

// Конфигурация сети
$seed = null;
$seed = 809471366; // Удачный вариант начального значения весов
$conf = [
	'name' => 'Iris',
	'speed' => 0.003,			// Скорость градиентного спуска (гиперпараметр)
	'momentum' => 0,			// Момент  (гиперпараметр)
	'activation' => 'leakyrelu',	// Функция активации
	'activationByLayers' => [1 => 'softmax'],
	'inputs' => 4,				// Количество входов X сети
	'layers' => [10, 3],			// Архитектура, только скрытые слои
	'bias' => true
];

// Инициализация сети
$net = new FF($conf);
$seed = $net->generateWs('auto', $seed);
//$net->printNet();

// Обучение
$res = $net->educate($dataSet, 200, ['shuffle' => true], $testSet);
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

// Тестовое опраделение сортов ирисов
echo '<br><br>TEST IRISes<br>';
$irises = ['setosa', 'versicolor', 'virginica'];
for ($i = 0; $i < 10; $i++) { 
	$item = $testSet[$i];

	// разделим тестовую запись на входные параметры и ответ-эталон
	$input = [];
	$output = [];
	foreach ($item as $j => $v) {
		if($j < 4) $input[] = $v;
		else $output[] = $v;
	}

	// определим сорт ириса по эталону
	$eta = null;
	foreach ($output as $j => $v) {
		if($v) $eta = $irises[$j];
	}

	// получим ответ нейросети
	$pred = null;
	$res = $net->predict($input);
	if($res['success']){
		$maxi = array_keys($res['result'], max($res['result']));
		$pred = $irises[$maxi[0]];
	}

	// сравним ответы
	$res = 'WRONG...';
	if($pred == $eta){
		$res = 'RIGHT!';
	}

	// Вывод
	echo 'Parameters: '.implode('; ', $input).' etalon: <b>'.$eta.'</b> predict: <b>'.$pred.'</b> - '.$res.'<br>';
}

//$net->save();

?>