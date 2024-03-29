<?php

// Class for handling HTTP requests to the InfluxDB server


class CURL {
    private $base_url;
    private $database;
    

    public function __construct($db_name, $url, $port) {
        $this->database = $db_name;
        $this->set_base_url($url, $port);
    }


    // Build base url (adds port to url supplied in construct)
    private function set_base_url($url, $port) {
        // Remove trailing slash if exists and append port to build base url
        if (substr($url, -1) === "/") {
            $this->base_url = substr($url, 0, -1) . ':' . $port;
        }
        else {
            $this->base_url = $url . ':' . $port;
        }
    }


    // GET request for database queries
    protected function get($query, $precision = 'ns') {
        // Build query URL
        $url = $this->base_url;
        $url .= "/query?pretty=true";           // Pretty arg gets JSON return
        $url .= "&db={$this->database}";        // Add database name
        $url .= "&epoch=$precision";            // Specify timestamp precision
        $url .= "&q=" . urlencode($query);      // Encode query
        
        // Create cURL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   // Get status as string
        
        return $this->executeCurl($curl);
    }

    
    // Default timestamp precision for InfluxDB is nanoseconds
    protected function post($data, $precision = 'ns') {
        // Build query URL
        $url = $this->base_url . "/write?";
        $url .= "precision=$precision";
        $url .= "&db={$this->database}";
        
        // Create cURL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        return $this->executeCurl($curl);
    }

    
    // Executes a supplied cURL
    private function executeCurl($curl) {
        $response = curl_exec($curl);
        
        $status = curl_getinfo($curl);
        
        if (curl_errno($curl)) {
            die('cURL request failed. Error number: ' . curl_error($curl));
            
            if ($status !== 200) {
                die('cURL request failed. HTTP status code: ' . $status);
            }
        }
        
        curl_close($curl);

        return json_decode($response, true);
    }
}



