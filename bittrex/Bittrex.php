<?php

	// NOTE: currency pairs are reverse of what most exchanges use...
	// For instance, instead of XPM_BTC, use BTC_XPM
	
	// 2x NOTE: The API actually uses a hyphen to separate pairs, but
	// I wanted this to match my Poloniex class cause I'm easily confused :)

	// 3x NOTE: All API calls utilize GET request

class Bittrex {
	
	protected $public_url = 'https://bittrex.com/api/v1.1/public/';

	// Add keys
	public function __construct() {

	}


	/************************************************************
	 * Public API Functions (market data/trade history/etc.)
	 * URL GET requests not mapped to account actions 
	 ************************************************************/

	// Get market info
	public function get_markets($pair = 'all') {
		$url = $this->public_url . 'getmarkets';
		$data = $this->retrieveJSON($url);

		if ($pair == 'all') {
			return $data;
		}

		$index = strpos($pair, '_');								// Get delimiter index
		$base = strtoupper(substr($pair, $index + 1));				// Get main trading coin
		$against = strtoupper(substr($pair, 0, $index));			// Get coin traded against

		foreach ($data as $row) {
			if ($row['MarketCurrency'] == $base && $row['BaseCurrency'] == $against) {
				return $row;
			}
		}

		return array();		// Return empty array if pair not found
	}


	// Get current ticker info for given pair
	public function get_ticker($pair) {
		$pair = $this->format_pair($pair);

		$url = $this->public_url . 'getticker?market=' . $pair;
		return $this->retrieveJSON($url);
	}


	// Get detailed market summary of last 24 hours
	public function get_market_summary($pair = 'all') {
		$url = $this->public_url . 'getmarketsummaries';
		$data = $this->retrieveJSON($url);

		if ($pair == 'all') {
			return $data;
		}

		$pair = $this->format_pair($pair);
		
		foreach ($data as $row) {
			if ($row['MarketName'] == $pair) {
				return $row;
			}
		}

		return array();		// Return empty array if pair not found
	}


	// Get current order book for pair
	// Type = both/sell/buy
	public function get_order_book($pair, $type = 'both') {
		$pair = $this->format_pair($pair);

		$url = $this->public_url . 'getorderbook?';
		$url .= 'market=' . $pair;
		$url .= '&type=' . $type;

		return $this->retrieveJSON($url);
	}


	// Return most recent trades
	// Default limit of 200 = max allowed by API
	public function get_market_history($pair, $limit = 200) {
		$pair = $this->format_pair($pair);

		$url = $this->public_url . 'getmarkethistory?';
		$url .= 'market=' . $pair;
		$data = $this->retrieveJSON($url);

		return array_slice($data, 0, $limit);
	}




	/************************************************************
	 * API request and misc helper functions
	 ************************************************************/

	// Format currency pair to replace hyphen with underscore
	private function format_pair($pair) {
		$pair = str_replace('_', '-', $pair);
		$pair = strtoupper($pair);
		return $pair;
	}


	// Perform a basic GET request to URL
	protected function retrieveJSON($URL) {
		// TODO: Throw exception instead of die
		$opts = array('https' =>
			array(
				'method'  => 'GET',
				'timeout' => 10 
			)
		);
		$context = stream_context_create($opts);
		$data = file_get_contents($URL, false, $context);
		$json = json_decode($data, true);

		if ($json['success']) {
			return $json['result'];
		}
		else {
			die($json['message']);
		}
	}
}