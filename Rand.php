<?php
/*
	Класс генерации случайных чисел для инициализации весов
		SEED генерации всегда формируется, если оно не передано извне и его можно получить функцией getSeed();

	Методы:
	 - uniform ($min, $max) - равномерное распределение случайных чисел в заданном интервале
	 - normal ($center, $dispers) - нормальное распределение случайных чисел с заданным ожиданием и отклонением
	 - havier ($n) / glorot ($n) - случайное число (вес) по методу Ксавьера/Глорота: W = U(-1/sqrt(n), 1/sqrt(n)), где n - число входных связей нейрона, U - равномерное распределение
	 - he ($n) / kayming ($n) - случайное число (вес) по методу Кайминга Хе: W = N(0, sqrt(2/n)), где n - число входных связей нейрона, N - нормальное распределение
 */


class Rand {

	private $seed = null;

	public function __construct($seed = null) {
		$this->seed = $seed;

		if(!$this->seed || !is_numeric($this->seed)){
			$this->seed = mt_rand(0, mt_getrandmax());
		}

		mt_srand($this->seed);

		return $this;
	}

	public function getSeed(){
		return $this->seed;
	}

	public function uniform($min = -1, $max = 1){
		$v = $min + abs($max - $min) * mt_rand(0, mt_getrandmax())/mt_getrandmax();

		return $v;
	}

	public function normal($cent = 0, $disp = 1){
		$x = mt_rand() / mt_getrandmax();
		$y = mt_rand() / mt_getrandmax();

		$v = sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $disp + $cent;

		return $v;
	}

	public function havier ($n = 1){
		$a = 1/sqrt($n);
		$v = $this->uniform(-$a, $a);

		return $v;
	}
	public function glorot ($n = 1){
		return $this->havier($n);
	}

	public function he ($n = 1){
		$a = sqrt(2/$n);
		$v = $this->normal(0, $a);

		return $v;
	}
	public function kayming ($n = 1){
		return $this->he($n);
	}
}
?>
