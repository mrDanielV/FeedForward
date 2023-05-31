Простая PHP-библиотека для постройния и обучения нейросетей Прямого Распространения

> Функции активации: sigmoid, hyperbolic tangent, ReLu, Leaky ReLu, softmax, linear, threshold

> Расчет ошибки: MSE, CrossEntropy

> Обучение методом обратного распространения (Backpropagation) с корректировкой весов методом градиентного спуска

Библиотека предназначена для решения задач классификации и апроксимации и хорошо подойдет для знакомства с миром нейросетей, если вам удобней начинать знакомится с ними именно на PHP. Библиотека FEED FORWARD (основной класс FF.php) работает в разы медленней библиотек, подобных TensorFlow для Python, но с сетями уровня решения статистических задач вполне справляется. Скажем, нейросеть глубиной 3-4 слоя 10x10 на ДатаСете размером в 10 000 строк легко обучается за 30 - 60 минут.

# Короткий мануал по использованию класса FF
## Пример конфигурации, обучения и тестирования сети

### Конфигурация сети

````
require_once 'includer.php';

$seed = null;

$conf = [
	'name' => 'Iris',
	'speed' => 0.003,
	'momentum' => 0,
	'activation' => 'leakyrelu',
	'activationByLayers' => [1 => 'softmax'],
	'inputs' => 4,
	'layers' => [10, 3],
	'bias' => true
];
````

### Инициация сети
````
$net = new FF($conf);
$seed = $net->generateWs('auto', $seed);
````

### Вывод архитектуры сети для отладки 
````
$net->printNet();
````

### Обучение сети
````
$res = $net->educate($dataSet, 200, ['shuffle' => true]);
if($net->isErrors()){
	$net->printErrors();
	die();
}
````

### График изменения ошибки в процессе обучения
````
$netServ = new NetService($net);
$graf = $netServ->graphEducateErrors();
echo $graf;
````

### Проверка по тестовому множеству (выборке)
````
$res = $net->test($testSet);
````

### Сохранение сети
````
$net->save();
````

### Использование 
````
$net = new FF('Iris');
$res = $net->predict($input);
````

## Полное описание формата конфигурации сети
````
[
	'name' => <string>, // Network name, used to store settings and weights
	'speed' => <float>, // Gradient Descent Speed (network hyperparameter)
	'momentum' => <float>, // Weight-error function offset moment (network hyperparameter)
	'activation' => <string>, // Activation function: sigmoid (default), tangh, relu, softmax, linear, threshold
	'activationByLayers' => [ // Activation function for the layer, layerIndex - layer index
		<layerIndex> => <string>
		]
	'inputs' => <int>, // Number of input neurons
	'scaleInputs' => // "Standardize" the input values:
		true/false // Vi = 1/log(Vi);
		[i => [min, max], ...], // Vi = (Vi - Vmin) / (Vmax - Vmin);
		// IMPORTANT! Min/Max for each crown index of the training sample must be saved and then assigned to the network during its operation
	'layers' => [<int>, ...], // Array of HIDDEN layers indicating the number of neurons in each
	'bias' => true/false/ // Presence of bias neurons (BIAS) on all network layers
	'onlyLast', // onlyLast - add BIAS only on the last hidden layer (to the output)
	'biasInput' => true/false, // Automatically pad input with one: input = [1.1, 1.2, 1.3] => [1.1, 1.2, 1.3, 1]
								// Unless otherwise specified, enabled when bias = true
	'regular' => <float> // Regularize the error estimate, E = E + L * SUM (|Wi|), set to L
]
````

## Формат массива весов связей сети
````
$ws = [array of weights [array of layers [array of input connections of each neuron], ...], ...]
````
*Example for network inputs = 3, layers = [2, 1]:*
````
$ws = [
	[[0.45, -0.12, 0.4], [0.78, 0.13, 0.84]], // first layer - N elements = N neurons in the first layer, Ni elements = number of input X
	[[1.5, -2.3]] // second layer - N elements = N neurons in the second layer, Ni elements = number of neurons in the first layer
];
````

## Обращение к сети c заданными весами, пример
````
$net = new FF($conf);
$net->setWs($ws);
$res = $net->predict([1, 0]);
$res = ['success' => true/false, 'result' => [1]]
````

## Функции обучения сети
````
forward ($input, $output, $validate) - прямое прохождение сети с расчетом ошибки при наличии эталона ($output)

back($output, $validate, $options) - обратное прохождение сети (backpropagation) с корректировкой весов

predict ($input, $validate) - прямое прохождение сети, алиас к forward без учета эталонного ответа, без расчета ошибки

learn ($input, $output) - одна итерация обучения, использующая forward + back

educate ($dataset, $eraN, $options, $testset) - полный курс обучения сети на обучающем DATA сете

test ($testset, $options) - проверка сети на тестовом множестве, с фиксацией ошибки (минимальной, средней, максимальной)
````

## Функция educate($dataset, $eraN, $options, $testset) - полный курс обучения.
````
$dataset <array> - обучающий индексированный двухмерный массив [i-строка, j-колонка],

$eraN <int> - количество эпох обучения

$options <array> - опции:
- 'outputs' <array> ([j1, j2, ...]) - индексы колонок-эталонов в $dataset, если не заданы, то используются последние N-колонок, где N = число нейронов выходного слоя
- 'shuffle' <bool> - случайным образом перемешивать $dataset перед каждой эпохой
- 'batch' <int> - размер батча (порции) корректировки весов, по умолчанию обучение проходит без батчей с корректировкой весов на каждой итерации внутри эпохи
- 'untilError' - величина ошибки на которой обучение останавливается автоматически, по умолчанию не используется ( = 0)

$testset <array> - тестовая выборка, если не пустая, то для каждой эпохи и/или батча фиксируется ошибка по тестовой выборке
````

## Инициация (генерация) весов
````
generateWs($type = 'auto', $seed = null, $params = []) = seed
	$type - метод случайной генерации: auto, uniform, normal, havier, glorot, he, kayming (подробнее о методах в классе Rand)
		auto - функция самостоятельно определяет наилучший метод для каждого слоя
	$params - атрибуты генерации для uniform и normal. 
		Для uniform это диапазон равномерного распределения
		Для normal - мат. ожидание и отклонение от нормали
````

**Примеры успешного обучения сетей на educate() - examples/.php**


> Для построения графиков используется усеченная библиотека "JpGraph PHP library version 4.4.1"
> (C) 2000-2010 Asial Corporatoin
