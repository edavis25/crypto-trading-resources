<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Libraries\influxdb\InfluxDB;

class ApiController extends Controller {

	// Multiply timestamps by 1billion to get nanoseconds for queries
	private $NANOSECOND_MULT = 1000000000;
	private $MAX_QUERY_LIMIT = 20000;
	

	public function __construct() {

	}

	public function history($pair) {
		// Get Params
		$start = Input::get('start');
		$period = Input::get('period');
		$limit = Input::get('limit');
		$pair = strtolower($pair);

		// Create DB
		$db = new InfluxDB('crypto', 'http://192.168.0.101');
		// Check tag is valid
		$valid_pairs = $db->getTagValues('pair');
		if (!in_array($pair, $valid_pairs)) {
			return 'Invalid pair';
		}

		// TODO: Check valid dates

		// Set default limit of 10k
		$limit = $this->validate_limit($limit);
		// Set default period of 15min
		$period = $this->validate_period($period);
		// Set dates
		$start = $start;// * $this->NANOSECOND_MULT;
		$end = $end;// * $this->NANOSECOND_MULT;

		// Build query
		$sql = "SELECT * FROM poloniex WHERE pair='$pair' AND time >= ($start * {$this->NANOSECOND_MULT}) AND time <= ($end * {$this->NANOSECOND_MULT}) LIMIT $limit";

		$json = $db->query_raw($sql);

		
		return response()->json($json);
		echo $sql;
		echo "<br>QUERY TIME!";
	}


	private function validate_dates($start, $end) {
		// Make sure valid dates given
		// If less than 2014, leave dates out
	}

	private function validate_period($period) {
		if (isset($period) && $period) {
			$period = ($period < 300) ? 300 : $period;
		}
		else {
			$period = 900;
		}
		return floor($period / 60);
	}

	private function validate_limit($limit) {
		if (isset($limit) && $limit){
			return ($limit > $this->MAX_QUERY_LIMIT) ? $this->MAX_QUERY_LIMIT : $limit;
		}
		else {
			return $this->MAX_QUERY_LIMIT;
		}
	}
}


// 1497477300000000000
// 1497477300 END
// 1497367300 START