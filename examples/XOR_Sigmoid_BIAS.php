<?php
/*
	Пример обучения и эксплуатации сети для операции XOR
	Sigmoid + BIAS
 */

require_once '../includer.php';

$seed = 1620700941;

// Конфигурация сети
$conf = [
	'name' => 'XOR_Sigmoid_BIAS',
	'speed' => 1,				// Скорость градиентного спуска (гиперпараметр)
	'momentum' => 0.8,			// Момент  (гиперпараметр)
	'activation' => 'sigmoid',	// Функция активации
	'inputs' => 2,				// Количество входов X сети
	'layers' => [4, 1],			// Архитектура, только скрытые слои
	'bias' => true
];

// Данные для обучения, последний столбец - эталон результата
$dataSet = [
	[1, 0, 1],
	[1, 1, 0],
	[0, 1, 1],
	[0, 0, 0]
];

// Инициализация сети
$net = new FF($conf);
$seed = $net->generateWs('auto', $seed);

// Обучение
$res = $net->educate($dataSet, 500);
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
	$value = $res['result'][0];
	$value = round($value, 2);
	echo $item[0].' * '.$item[1].' = '.$value.'<br>'."\n";
}
echo '<br><br>';

// Чтение НС
$net = new FF('XOR_Sigmoid_BIAS');
$net->printNet();
echo '<br>';

// Пример использования
echo 'Predict example<br>';
$res = (new FF('XOR_Sigmoid_BIAS'))->predict([1, 0]);
echo '1 xor 0 = '.round($res['result'][0]);

// Сохранение сети
//$net->save();
?>