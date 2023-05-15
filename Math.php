<?php
/*
	Класс математических функций нейросети
	
	Вызов функции с проверкой наличия: Math::fnc($funcname, [<массив аргументов>]);
	
	Функции активации нейронов
		sigmoid - логистическая функция, (1 / (1 + е(-x))), derivative: ((1 - x) * x)
		tangh - гиперболический тангенс, ((e(2x) – 1) / (e(2x) + 1)), derivative: 1 – x^2
		relu - ReLu, {x при x > 0, 0 при x <= 0}, derivative: {1 при x > 0, 0 при x <= 0}
		leakyrelu - Leaky ReLu (неплотная) ReLu - в отрицательной части аргумента линейная y = 0.01x
		threshold - пороговая функция, {1 при x > 0, 0 при x <= 0}, derivative: 1
		linear - линейная, y = x, derivative = 1
		softmax - стандартизация, y = x / SUM(vs), derivative: 1

	Функции ошибки 
		MSE - средне квадратичная ошибка = SUM ((ai - vi)^2) / n
		crossEntropy - перекрестная энтропия = SUM (-ai * log(vi) / n
 
	Стандартизация и Нормализация
		scaleMinMax - нормализация: X = (X - Xmin) / (Xmax - Xmin)
		descaleMinMax - де-дормализация: X = X * (Xmax - Xmin) + Xmin
		scaleLog - стандартизация: X = 1 / log(X)
		descaleLog - де-стандартизация: e ^ (1/x)
		zscore - стандартизация: Xi = ( Xi - µ ) / σ
 */

class Math {

	public static function fnc($name, $args = []) {
		if(!$name || empty($args)) return 0;

		$self = new self();

		if(!method_exists($self, $name)){
			return 0;
		}

		$r = call_user_func_array([$self, $name], $args);

		return $r;
	}

	public static function fncExist($name) {
		if(!$name) return false;

		$self = new self();
		return method_exists($self, $name);
	}
	
	public function sigmoid($x, $d = false){
		// (1 / (1 + е(-x))), derivative: ((1 - x) * x)
		if(!is_numeric($x)) return 0;
		if($d){
			return (1 - $x) * $x;
		}
		return (1 / (1 + exp(-$x)));
	}

	public function tangh($x, $d = false){
		// ((e(2x) – 1) / (e(2x) + 1)), derivative: 1 – x^2
		if(!is_numeric($x)) return 0;
		if($d){
			return (1 - $x ** 2);
		}
		return (exp(2*$x) - 1) / (exp(2*$x) + 1);
	}

	public function relu($x, $d = false){
		// {x при x > 0, 0 при x <= 0}, derivative: {1 при x > 0, 0 при x <= 0}
		if(!is_numeric($x)) return 0;
		if($d){
			if($x > 0) return 1;
			else return 0;
		}
		return max(0, $x);
	}

	public function leakyrelu($x, $d = false){
		// {x при x > 0, 0.01*x при x <= 0}, derivative: {1 при x > 0, 0.01 при x <= 0}
		if(!is_numeric($x)) return 0;
		if($d){
			if($x > 0) return 1;
			else return 0.01;
		}
		return max(0.01*$x, $x);
	}

	public function softmax($v = [], $d = false){
		// y = exp(xi) / SUM(exp(xsi))
		// При условии, что на выходном слое производится классификация и классы кодируются как 1 или 0 на соответствующих выходных нейронах
		// Градиент softmax равен "эталон - значение", что уже учтено в основной математике класса, поэтому за производную достаточно взять 1
		if($d && is_array($v)){
			return $this->softmaxD($v);
		}
		else if($d && is_numeric($v)){
			return 1;
			//return $this->softmaxDI($v);
		}
		
		$v = array_map('exp',array_map('floatval', $v));
		$sum = array_sum($v);

		foreach($v as $index => $value) {
			$v[$index] = $value/$sum;
		}

		return $v;
	}

	public function softmaxDI($y){
		// производная softmax на i-том нейроне слоя по его значению
		if(!is_numeric($y)) return 0;
		return $y *(1 - $y);
	}

	public function softmaxD($v = []){
		// матрица производных по softmax (Якобиан): Jij = Vi (q - Vj), где q - флаг равенства i и j (1 - равны, 0 - не равны)
		if(!is_array($v) || empty($v)) return [];

		// softmax Jacobian
		$jac = [];
		foreach ($v as $i => $vi) {
			$jac[$i] = [];
			foreach ($v as $j => $vj) {
				if($i == $j){
					$jac[$i][] = $vi * (1 - $vj);
				}else{
					$jac[$i][] = -1 * $vi * $vj;
				}
			}
		}

		return $jac;
	}

	public function linear($x, $d = false){
		// y = x, derivative: 1
		if(!is_numeric($x)) return 0;
		if($d){
			return 1;
		}
		return $x;
	}

	public function threshold($x, $d = false){
		// {1 при x > 0, 0 при x <= 0}, derivative: 1
		if(!is_numeric($x)) return 0;
		if($d){
			return 1;
		}

		if($x > 0) return 1;
		else return 0;
	}

	public function MSE ($values = [], $etalons = []) {
		// values = массив фактических значений НС, etalons = массив ожидаемых (верных) значений
		$n = sizeof($values);
		$se = 0;
		foreach ($values as $i => $v) {
			$a = (isset($etalons[$i]))?$etalons[$i]:0;
			$se = $se + ($a - $v) ** 2;
		}

		return $se/$n;
	}

	public function crossEntropy ($values = [], $etalons = []){
		// values = массив фактических значений НС, etalons = массив ожидаемых (верных) значений
		$n = sizeof($values);
		$se = 0;
		foreach ($values as $i => $v) {
			$a = (isset($etalons[$i]))?$etalons[$i]:0;
			$se = $se - $a * log($v);
		}

		return $se/$n;
	}

	public function scaleMinMax($x, $min = 0, $max = 1000){
		// Нормализация: X = (X - Xmin) / (Xmax - Xmin)
		if(!is_numeric($x) || !is_numeric($min) || !is_numeric($max) || $min == $max){
			return 0;
		}
		return ($x - $min) / ($max - $min);
	}

	public function descaleMinMax($x, $min = 0, $max = 1000){
		// Де-Нормализация: X = X * (Xmax - Xmin) + Xmin
		if(!is_numeric($x) || !is_numeric($min) || !is_numeric($max) || $min == $max){
			return 0;
		}
		return $x * ($max - $min) + $min;
	}

	public function scaleLog($x){
		// Стандартизация: X = 1 / log(X)
		if(!is_numeric($x)){
			return 0;
		}
		return 1/log($x);
	}

	public function descaleLog($x){
		// Де-Стандартизация: e ^ (1/x)
		if(!is_numeric($x)){
			return 0;
		}
		return M_E ** (1 / $x);
	}

	public function zscore($x = []){
		// стандартизация вектора: Xi = ( Xi - µ ) / σ, где σ - стандартное отклонение, µ - среднее, а Xi - значения входных характеристик.
		$st = (new DataService())->zscore($x);
		return $st;
	}

	public function scalarVM ($v = [], $ml = []){
		$res = 0;
		foreach ($v as $i => $vi) {
			if(isset($ml[$i])){
				$res+= $vi * $ml[$i];
			}
		}
		return $res;
	}

	public function vmMulty ($v = [], $m = []){
		$res = [];
		foreach ($v as $i => $vi) {
			if(isset($m[$i])){
				$res[] = $this->scalarVM($v, $m[$i]);
			}
		}
		return $res;
	}

	public function vectorMatrixMultiply ($v = [], $m = []){
		// Умножение вектора на матрицу  Rj = ∑Vi*Mij (i = 1 … n, j = 1 … m).
		// Размерность вектора должна быть равна количеству строк матрицы
		if(!is_array($v) || !is_array($m)){
			return null;
		}
		if(empty($v) || empty($m) || !isset($v[0]) || !isset($m[0][0])){
			return [];
		}

		if(sizeof($v) != sizeof($m)){
			return false;
		}

		$r = [];


		for ($j = 0; $j < sizeof($m[0]); $j++) {
			$r[$j] = 0;
			for ($i = 0; $i < sizeof($v); $i++) { 
				$r[$j]+= $v[$i] * $m[$i][$j];
			}
		}

		return $r;
	}
}
?>
