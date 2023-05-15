<?php
// Объектный класс связи между нейронами сети
class Link {

	public $A = null;
	public $B = null;
	private $layer = 0;
	private $w = 0;
	private $dw = 0;


	public function __construct($A = null, $B = null, $layer = 0, $w = 0) {

		$this->setA($A);
		$this->setB($B);
		$this->setW($w);
		$this->layer = $layer;

		return $this;
	}

	public function __toString(){
		$neuron = '<none>';

		if($B instanceof Neuron){
			$neuron = $B->index;
		}

		return 'LINK to: '.$this->$layer.'-'.$neuron.' = '.$this->w;
	}

	public function setA($A = null){
		$this->A = $A;

		if($A instanceof Neuron){
			$A->addOuts($this);
		}
	}

	public function setB($B = null){
		$this->B = $B;

		if($B instanceof Neuron){
			$B->addIncome($this);
		}
	}

	public function setW($w = 0){
		if(is_nan($w)) $w = 0;
		if(is_infinite($w)) $w = PHP_INT_MAX;
		
		$this->w = $w;
	}

	public function correctW($dw = 0){
		if(is_nan($dw)) $dw = 0;
		if(is_infinite($dw)) $dw = PHP_INT_MAX;

		$this->dw = $dw;
		$this->setW($this->w + $dw);
		//$this->w = $this->w + $dw;
	}

	public function getW(){
		return $this->w;
	}

	public function getDW(){
		return $this->dw;
	}

	public function getX(){
		if($this->A instanceof Neuron){
			return $this->A->get();
		}
		return $this->A;
	}
}
?>
