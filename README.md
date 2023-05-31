PHP Simple Library for building and educating Feedforward Neural Networks

> Activation functions: sigmoid, hyperbolic tangent, ReLu, Leaky ReLu, softmax, linear, threshold

> Loss functions: MSE, CrossEntropy

> Backpropagation training with gradient descent weight adjustment

# Short Manual of using FF class
## Example

### Network configuration

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

### Init Network
````
$net = new FF($conf);
$seed = $net->generateWs('auto', $seed);
````

### debub print Network
````
$net->printNet();
````

### Educate Network
````
$res = $net->educate($dataSet, 200, ['shuffle' => true]);
if($net->isErrors()){
	$net->printErrors();
	die();
}
````

### Graph of error change during training
````
$netServ = new NetService($net);
$graf = $netServ->graphEducateErrors();
echo $graf;
````

### Checking on the test set
````
$res = $net->test($testSet);
````

### Use
````
$net = new FF('Iris');
$res = $net->predict($input);
````

## Network Configuration Format
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

## Network Link Weight Array Format
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

## Accessing a network with given weights, example
````
$net = new FF($conf);
$net->setWs($ws);
$res = $net->predict([1, 0]);
$res = ['success' => true/false, 'result' => [1]]
````

## Network learning functions
````
forward ($input, $output, $validate) - forward network traversal with error calculation in the presence of a reference ($output)

back($output, $validate, $options) - reverse network traversal (backpropagation) with weight adjustment

predict ($input, $validate) - direct network traversal, alias to forward without taking into account the reference response, without calculating the error

learn ($input, $output) - one learning iteration using forward + back

educate ($dataset, $eraN, $options, $testset) - complete network training course on DATA training set

test ($testset, $options) - test the network on the test set, fixing the error (minimum, average, maximum)
````

## The educate($dataset, $eraN, $options, $testset) function is a complete tutorial.
````
$dataset <array> - training indexed two-dimensional array [i-row, j-column],

$eraN <int> - number of training epochs

$options <array> - options:
- 'outputs' <array> ([j1, j2, ...]) - indices of reference columns in $dataset, if not set, then the last N columns are used, where N = number of output layer neurons
- 'shuffle' <bool> - randomly shuffle $dataset before each epoch
- 'batch' <int> - the size of the batch (portion) of weight adjustment, by default, training takes place without batches with weight adjustment at each iteration within the epoch
- 'untilError' - error value at which training stops automatically, not used by default ( = 0)

$testset <array> - test set, if not empty, then for each epoch and/or batch, a test set error is fixed
````

## Initiation (generation) of weights
````
generateWs($type = 'auto', $seed = null, $params = []) = seed
	$type - random generation method: auto, uniform, normal, havier, glorot, he, kayming (more about methods in the Rand class)
		auto - the function independently determines the best method for each layer
	$params - generation attributes for uniform and normal.
		For uniform, this is the range of the uniform distribution
		For normal - mat. expectation and deviation from the normal
````

**More examples in the examples/ directory**


> To build graphs, a truncated library "JpGraph PHP library version 4.4.1" is used
> (C) 2000-2010 Asial Corporatoin
