<?php
/*
	Класс инструментов обслуживания DATASET нейросети: 
		getMin(), getMax(), getMinMax - Получение Min/Max по массиву/колонке матрицы
		getScale() - Получение Min/Max по колонкам всего DATASET
		getColumn() - Получение отдельной колонки из DATASET
		setColumn() - Обновление отдельной колонки в DATASET
		getMiddle() - Среднее значение по массиву значений
		getStd() - Стандартное отклонение по массиву значений
		zscore() - Стандартизация значений массива
		getStandarted() - стандартизация значений указанных колонок DATASET методом zscore
 */

class DataService {

	private $data = [[]];

	public function __construct($dataset = []) {
		if(!is_array($dataset)){
			$dataset = [[]];
		}
		$this->setData($dataset);
		
		return $this;
	}

	public function setData($dataset = []){
		if(!empty($dataset) && is_array($dataset) && isset($dataset[0][0]) && is_scalar($dataset[0][0])){
			$this->data = $dataset;
		}

		return $this;
	}

	public function detData(){
		return $this->data;
	}

	public function getMin ($array = []){
		// Min по массиву $array
		if(!is_array($array)){
			return null;
		}
		return min($array);
	}

	public function getMax ($array = []){
		// Max по массиву $array
		if(!is_array($array)){
			return null;
		}
		return max($array);
	}

	public function getMinMax ($array = []){
		// [Min, Max] по массиву $array
		return ['min' => $this->getMin($array), 'max' => $this->getMax($array)];
	}

	public function getMiddle ($array = []){
		// Получение среднего значения по массиву
		if(!is_array($array)){
			return null;
		}

		$a = array_filter($array);
		$m = array_sum($array)/count($array);

		return $m;
	}

	public function getStd ($array = []){
		// Получение стандартного отклонения по массиву
		if(!is_array($array)){
			return null;
		}

		$l = sizeof($array);
		$m = $this->getMiddle($array);

		$sm = 0;
		foreach ($array as $v) {
			$sm+= ($v - $m) ** 2;
		}
		$sm = $sm / $m;

		return ($sm > 0)?sqrt($sm):0;
	}

	public function zscore($array = []){
		// стандартизация вектора: Xi = ( Xi - µ ) / σ, где σ - стандартное отклонение, µ - среднее, а Xi - значения входных характеристик.
		if(!is_array($array)){
			return null;
		}

		$m = $this->getMiddle($array);
		$sm = $this->getStd($array);

		foreach ($array as $i => $v) {
			if(!is_numeric($v)){
				continue;
			}

			$array[$i] = ($v - $m) / $sm;
		}

		return $array;
	}

	public function getColumn ($index, $dataset = []){
		// Колонка массива DataSet = [[], [], ...] c заданным индексом
		// Возвращает массив [0..n] значений колонки
		if(!empty($dataset)) $this->setData($dataset);

		$result = [];
		foreach ($this->data as $i => $line) {
			if(is_array($line) && isset($line[$index])){
				$result[] = $line[$index];
			}
		}

		return $result;
	}

	public function setColumn ($index, $column, $dataset = []){
		// обновление колонки данных $column в $dataset по индексу $index
		if(!empty($dataset)) $this->setData($dataset);

		foreach ($this->data as $i => $row) {
			if(!is_array($row)){
				continue;
			}
			foreach ($row as $j => $v) {
				if($j === $index){
					$this->data[$i][$j] = $column[$i];
				}
			}
		}

		return $this->data;
	}

	public function getScale($indexes = [], $dataset = []){
		// возврат [index => [Xmin, Xmax], …]
		if(!empty($dataset)) $this->setData($dataset);

		$result = [];

		$line = isset($this->data[0])?$this->data[0]:[];
		for ($i = 0; $i < sizeof($line); $i++) {
			$column = $this->getColumn($i);
			$result[$i] = $this->getMinMax($column);
		}

		return $result;
	}

	public function getStandarted($indexes = [], $dataset = []){
		// Стандартизация указанных колонок ($indexes) в $dataset
		if(!empty($dataset)) $this->setData($dataset);

		foreach ($indexes as $index) {
			$column = $this->getColumn($index);
			$column = $this->zscore($column);
			$this->setColumn($index, $column);
		}

		return $this->data;
	}
	
}
?>
