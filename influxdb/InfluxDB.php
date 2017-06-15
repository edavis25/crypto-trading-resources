<?php

// Class for interacting with the Influx database 

require_once 'CURL.php';
require_once 'ResultSet.php';

class InfluxDB extends CURL {
    
    // Default port = 8086
    public function __construct($db_name, $url, $port = '8086') {
        parent::__construct($db_name, $url, $port);
    }


    // Returns query results in raw JSON format (InfluxDB default)
    public function query_raw($query) {
        return $this->get($query);
    }
    

    // Returns query results as a ResultSet object
    public function query($query, $precision = 'ns') {
        return new ResultSet($this->get($query, $precision));
    }
    

    // Get database users helper function
    public function getUsers() {
        $query = "SHOW USERS";
        return $this->query($query);
    }

    // Return measurement names as plain array
    public function getMeasurements() {
        $query = "SHOW MEASUREMENTS";
        $result = $this->query($query)->getResults();

        // Build return array
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row->data['name'];
        }
        return $arr;
    }
    
    public function getTagValues($key) {
        $query = "SHOW TAG VALUES WITH KEY = $key";
        $result = $this->query($query)->getResults();

        // Build return array
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row->data['value'];
        }
        return $arr;
    }

    // Get first record for measurement helper function 
    public function getFirstRecord($measurement, $precision = 'ns') {
        $query = "SELECT * FROM $measurement ORDER BY time ASC LIMIT 1";
        return $this->query($query, $precision);
    }


    // Get last record for a measurment helper function
    public function getLastRecord($measurement, $precision = 'ns') {
        $query = "SELECT * FROM $measurement ORDER BY time DESC LIMIT 1";
        return $this->query($query, $precision);
    }


    // Insert an array of data into database (each item in array = new record/row)
    // NOTE: Very large arrays can quickly exceed default memory limits
    public function insert($data = array(), $precision = 'ns') {
        $str = implode("\n", $data);
        $this->post($str, $precision);
    }
    

}
