<?php

// Boilerplate result class for queries - all properties provided as key/values
// in the the construct array and are accessed with magic GET/SET functions

class Result {

	public $data = array();

	// Array keys = property names & values = property values
	public function __construct($arr = array()) {
		$this->setProperties($arr);
	}


	// Magic SET to access properties inside $data
	public function __set($key, $val) {
		if (array_key_exists($key, $this->data)) {
			$this->data[$key] = $val;
		}
	}


	// Magic GET to access properties inside $data
	public function __get($key) {
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}
	}


	// Initial SET of properties from construct array
	private function setProperties($arr) {
		foreach ($arr as $key=>$val) {
			$this->data[$key] = $val;
		}
	}

}