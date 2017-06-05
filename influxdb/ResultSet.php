<?php
require 'Result.php';

class ResultSet {
    private $columns;
    private $database;
    private $results;
    
    // JSON array returned from InfluxDB query
    public function __construct($json_array) {
        $this->setDatabase($json_array);
        $this->setColumns($json_array);
        $this->setResults($json_array);
    }
    
    // Set column names as an array
    public function setColumns($cols) {
        if (isset($cols) && isset($cols['results'][0]['series'][0]['columns'])) {
            $this->columns = $cols['results'][0]['series'][0]['columns'];
        }
        else {
            $this->columns = array();
        }
    }

    // Set name of database
    public function setDatabase($db) {
        if (isset($db) && isset($db['results'][0]['series']['name'])) {
            $this->database = $db['results'][0]['series'][0]['name'];
        }
        else {
            $this->database = null;
        }
    }

    // Create array of Result objects
    public function setResults($values) {
        $arr = array();

        if (isset($values) && isset($values['results'][0]['series'][0]['values'])) {
            //$this->results = $values['results'][0]['series'][0]['values'];

            $json = $values['results'][0]['series'][0]['values'];
            foreach ($json as $row) {
                // Combine column names w/ 
                $comb = array_combine($this->getColumns(), $row);
                $arr[] = new Result($comb);
            }
        }

        $this->results = $arr;
    }
    
    public function getDatabase() {
        return $this->database;
    }
    public function getColumns() {
        return $this->columns;
    }
    public function getResults() {
        return $this->results;
    }
}