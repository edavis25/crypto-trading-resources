<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Libraries\influxdb\InfluxDB;
use Exception;


class ApiController extends Controller {

	// Multiply timestamps by 1billion to get nanoseconds for queries
	private $NANOSECOND_MULT = 1000000000;
	// Max limit for number of query results
	private $MAX_QUERY_LIMIT = 5000;
	// Min time period for queries
	//private $MIN_TIME_PERIOD = 300; 		// 300 seconds = 5 minutes
	private $MIN_TIME_PERIOD = 5; // Minutes
	// Default time period for queries
	//private $DEFAULT_TIME_PERIOD = 900;	// 900 seconds = 15 minutes
	private $DEFAULT_TIME_PERIOD = 5; // Minutes
	
	private $db;

	public function __construct() {
		$this->db = new InfluxDB('crypto', 'http://192.168.0.101');	
	}

	public function history($pair) {

		try {
			$pair = $this->validate_pair($pair);
			$sql = $this->build_history_query($pair);

			//$json = $db->query_raw($sql, 's');
			$json = $this->db->query($sql, 's');
			//$json = json_encode($json->getResults(), JSON_PRETTY_PRINT );
			
			$arr = array();
			foreach ($json->getResults() as $row) {
				$row->data['high']  = $this->format_num($row->data['high']);
				$row->data['low']  = $this->format_num($row->data['low']);
				$row->data['quote_volume']  = $this->format_num($row->data['quote_volume']);
				$row->data['volume']  = $this->format_num($row->data['volume']);
				$row->data['weighted_average']  = $this->format_num($row->data['weighted_average']);
				$arr[] = $row->data;
			}

			return response()->json($arr);

		}
		catch (Exception $ex) {
			return response()->json(array('error' => $ex->getMessage()));
		}

	}


	private function build_history_query($pair) {

		$start = Input::get('start');
		$start = $this->validate_date($start);

		// Get time period
		$period = Input::get('period');
		$period = $this->validate_period($period);

		// Get results limit
		$limit = Input::get('limit');
		$limit = $this->validate_limit($limit);

		// If min time period is selected, don't aggregate query and simply
		// run standard query as items are in DB (this is faster and avoids)
		// bug of first and last results having null values
		if ($period == $this->MIN_TIME_PERIOD) {
			$sql = "SELECT open, close, high, low, quote_volume, volume, weighted_average ";
			$sql .= "FROM poloniex ";
			$sql .= "WHERE pair='$pair' ";
		}
		else {
			$sql = "SELECT FIRST(open) AS open, ";
			$sql .= "LAST(close) AS close, ";
			$sql .= "MEAN(high) AS high, ";
			$sql .= "MEAN(low) AS low, ";
			$sql .= "MEAN(quote_volume) AS quote_volume, ";
			$sql .= "MEAN(volume) AS volume, ";
			$sql .= "MEAN(weighted_average) AS weighted_average ";
			$sql .= "FROM poloniex ";
			$sql .= "WHERE pair='$pair' ";
		}

		// Set start time if given or default for last 30 days
		if ($start) {
			$sql .= "AND time >= {$start}s AND time <= now() ";
		}
		else {
			$sql .= "AND time >= now() - 14d AND time <= now() ";
		}

		// Set group by bucket (if not min period)
		$sql .= ($period == $this->MIN_TIME_PERIOD) ? '' : "GROUP BY time({$period}m) ";

		$sql .= "LIMIT $limit";

		return $sql;
	}

	private function validate_db() {
		if (!$this->db) {
			throw new Exception('Could not connect to database');
		}
		return $this->db;
	}

	private function validate_pair($pair) {
		// Get trading pair & check if valid
		$pair = strtolower($pair);
		$valid_pairs = $this->db->getTagValues('pair');
		if (!in_array($pair, $valid_pairs)) {
			throw new Exception('Invalid trading pair');
		}
		return $pair;
	}

	private function validate_date($start) {
		if ($start && !filter_var($start, FILTER_VALIDATE_INT)) {
  			throw new Exception('Invalid start time. Use only integers for UNIX timestamp');
		}

		return $start;
	}

	private function validate_period($period) {
		if ($period && !filter_var($period, FILTER_VALIDATE_INT)) {
  			//return (response()->json(array('error' => 'Invalid time period')));
  			throw new Exception('Invalid time period. Use only integer values representing period in minutes');
		}

		if (isset($period) && $period) {
			// Enforce minimum time period of 5 mins
			$period = ($period < $this->MIN_TIME_PERIOD) ? $this->MIN_TIME_PERIOD : $period;
		}
		else {
			// Set default time period if nothing specified
			$period = $this->DEFAULT_TIME_PERIOD;
		}

		return $period;

		// Return period in minutes (60 = 1 min)
		return floor($period / 60);
	}


	private function validate_limit($limit) {
		if ($limit && !filter_var($limit, FILTER_VALIDATE_INT)) {
  			//return (response()->json(array('error' => 'Invalid time period')));
  			throw new Exception('Invalid limit. Use only integer values representing limit for number of results');
		}

		if (isset($limit) && $limit){
			return ($limit > $this->MAX_QUERY_LIMIT) ? $this->MAX_QUERY_LIMIT : $limit;
		}
		else {
			return $this->MAX_QUERY_LIMIT;
		}
	}


	private function format_num($num) {
        // 8 decimals w/ no commma separators
        return (float)number_format($num, 8, '.', '');
    }
}



// Get start date
		/*
		$start = Input::get('start');
		
		// Get time period
		$period = Input::get('period');
		$period = $this->validate_period($period);

		// Get results limit
		$limit = Input::get('limit');
		$limit = $this->validate_limit($limit);
		*/


		// TODO: Check valid dates

		// Build query
		//$sql = "SELECT * FROM poloniex WHERE pair='$pair' AND time >= ($start * {$this->NANOSECOND_MULT}) LIMIT $limit";
		/*
		$sql = "SELECT * FROM poloniex WHERE pair='$pair' ";
		if ($start) {
			$sql .= "AND time >= ($start * {$this->NANOSECOND_MULT}) ";
		}
		$sql .= "LIMIT $limit";
		*/

		// TEST
		//$sql = "SELECT MEAN(close) AS close, MEAN(high) AS high FROM poloniex WHERE pair='btc_doge' AND time >= 1497502500s AND time < now() GROUP BY TIME(15m)";

		//$sql = "SELECT pair AS pair, ";