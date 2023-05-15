<?php
// Класс нейрона сети
class Neuron {

	public $type = 'plane'; // plane, bias
	public $value = 0;
	public $layer = 0;
	public $index = 0;
	public $incomes = [];
	public $outs = [];
	public $dE = 0;


	public function __construct($layer = 0, $index = 0, $type = 'plane') {

		$this->layer = $layer;
		$this->index = $index;
		$this->type = $type;

		return $this;
	}

	public function __toString(){
		if($this->type == 'bias'){
			return 'BIAS #'.$this->index.' on Layer #'.$this->layer;
		}
		return 'NEURON #'.$this->index.' on Layer #'.$this->layer;
	}

	public function set($v = 0){
		if($this->type == 'bias'){
			$this->value = 1;
		}
		$this->value = $v;
	}

	public function get(){
		if($this->type == 'bias'){
			return 1;
		}
		return $this->value;
	}

	public function addIncome($link){
		$this->incomes[] = $link;
	}

	public function addOuts($link){
		$this->outs[] = $link;
	}

	public function getSum(){
		if(!is_array($this->incomes) || empty($this->incomes)){
			return 0;
		}
		$s = 0;
		foreach ($this->incomes as $link) {
			$s = $s + $link->getW() * $link->getX();
		}

		return $s;
	}

	public function setError($dE = 0, $L = 0){
		if(is_nan($dE)) $dE = 0;
		if(is_infinite($dE)) $dE = PHP_INT_MAX;

		// Величина регуляризации потерь L1
		if($L && !empty($this->incomes)){
			$s = 0;
			foreach ($this->incomes as $link) {
				$s = $s + abs($link->getW());
			}
			$dE = $dE - $L * $s;
		}

		$this->dE = $dE;
	}

}
?>
