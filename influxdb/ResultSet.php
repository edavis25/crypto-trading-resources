<?php

// Custom result set object for returning query results

require 'Result.php';

class ResultSet {

    private $columns;           // Array of column names
    private $database;          // String database name
    private $results;           // Array of Result objects
    
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
            $json = $values['results'][0]['series'][0]['values'];
            
            // Combine column names as keys for array to create result objects
            foreach ($json as $row) {
                $comb = array_combine($this->getColumns(), $row);
                $arr[] = new Result($comb);
            }
        }

        $this->results = $arr;
    }
    

    // Getter functions
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