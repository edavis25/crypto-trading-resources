<?php

require_once 'CURL.php';
require_once 'ResultSet.php';

class InfluxDB extends CURL {
    
    public function __construct($db_name, $url, $port = '8086') {
        parent::__construct($db_name, $url, $port);
    }

    public function query_raw($query) {
        return $this->get($query);
    }
    
    public function query($query, $precision = 'ms') {
        return new ResultSet($this->get($query, $precision));
    }
    
    public function getUsers() {
        $query = "SHOW USERS";
        return $this->query($query);
    }
    
    public function getFirstRecord($measurement) {
        $query = "SELECT * FROM $measurement ORDER BY time ASC LIMIT 1";
        return $this->query($query);
    }

    public function getLastRecord($measurement, $precision = 'rfc3339') {
        $query = "SELECT * FROM $measurement ORDER BY time DESC LIMIT 1";
        return $this->query($query, $precision);
    }

    public function insert($data, $precision = 'ns') {
        $date = new DateTime();
        $now = $date->getTimestamp();
        $now1 = $now + 1;
        //$data = "poloniex,pair=doge/btc high=100,low=99 $now\ntimepoloniex,pair=doge/btc high=90,low=89 $now\npoloniex,pair=doge/btc high=80,low=79 $now";
        //$data = array();
        //$data[] = "poloniex,pair=doge/btc high=100,low=99 $now";
        //$data = "poloniex,pair=doge/btc high=1,low=2 $now\npoloniex,pair=doge/btc high=7,low=8 $now1";
        
        $str = implode("\n", $data);
        $this->post($str, $precision);
    }
    
    


}


/* public function query($query, $raw_json = false) {
    if ($raw_json) {
        return $this->get($query);
    }
    else {
        return new ResultSet($this->get($query));
    }
}*/