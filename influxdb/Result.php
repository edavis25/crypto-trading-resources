<?php

class Result {

	public $data = array();

	public function __construct($arr = array()) {
		$this->setProperties($arr);
	}

	public function __set($key, $val) {
		if (array_key_exists($key, $this->data)) {
			$this->data[$key] = $val;
		}
	}

	public function __get($key) {
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}
	}

	private function setProperties($arr) {
		foreach ($arr as $key=>$val) {
			$this->data[$key] = $val;
		}
	}

}