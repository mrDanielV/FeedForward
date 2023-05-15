<?php
/*
	Пример обучения и эксплуатации сети для операции XOR
	Softmax
 */
 
require_once '../includer.php';

$seed = 593053583;

// Конфигурация сети
$conf = [
	'name' => 'XOR_Softmax',
	'speed' => 0.01,			// Скорость градиентного спуска (гиперпараметр)
	'momentum' => 0,			// Момент  (гиперпараметр)
	'activation' => 'softmax',	// Функция активации
	'inputs' => 2,				// Количество входов X сети
	'layers' => [4, 3, 2],		// Архитектура, только скрытые слои
	'bias' => true
];

// Данные для обучения, последние два столбца - эталоны классов ($dataSet[][2] = 1 для "1", $dataSet[][3] = 1 для "0")
$dataSet = [
	[1, 0, 1, 0],
	[1, 1, 0, 1],
	[0, 1, 1, 0],
	[0, 0, 0, 1]
];

// Инициализация сети
$net = new FF($conf);
$seed = $net->generateWs('normal', $seed, [0, 3]);
//$net->printNet();

// Обучение
$res = $net->educate($dataSet, 1000);
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

// Контрольная проверка работы сети
foreach ($dataSet as $item) {
	$input = [$item[0], $item[1]];
	$res = $net->predict($input);

	// условные вероятности классов
	$oneP = $res['result'][0];
	$zeroP = $res['result'][1];

	$value = 1;
	if($zeroP > 0.6){
		$value = 0;
	}

	echo $item[0].' * '.$item[1].' = '.$value.'<br>'."\n";
}
echo '<br><br>';

// Сохранение сети
// $net->save();


?>