<?php

require 'poloniex/Poloniex.php';
require 'influxdb/InfluxDB.php';

// Poloniex API keys
const KEY = '';
const SECRET = '';

// Other constants
const FIVE_MINUTES = 300;
const TWO_HOURS = 7200;
const MAX_INSERT_BATCH = 3000;

// Set config options
set_time_limit(TWO_HOURS);
ini_set('memory_limit','512M');

$polo = new Poloniex(KEY, SECRET);
$db = new InfluxDB('crypto', '192.168.0.101');

// Get time for optimizing
$start = time();
echo "START TIME: " . $start . "<br>";

// Get trading pairs
$pairs = $polo->get_trading_pairs();

$idx = 0;
foreach ($pairs as $pair) {
	// Check if pair already scraped (because I scraped them in chunks)
	if (already_exists($db, $pair)) {
		continue;
	}

	echo "STARTED SCRAPING $pair <br>";

	scrape_pair($db, $polo, $pair);

	echo "FINISHED SCRAPING $pair <br>";
	echo "<br>==========================================================<br>";

	// Break after scraping a few to avoid server running out of memory
	$idx += 1;
	if ($idx == 5) {
		break;
	}
}

echo "DONE!<br>";
$end = time();
echo "END TIME: " . $end . "<br>";
echo "ELAPSED: " . ($end - $start);


function scrape_pair($db, $polo, $pair) {
	$data = $polo -> get_chart_data($pair, 0000000000, 9999999999, FIVE_MINUTES);
	echo "NUMBER RESULTS - $pair: " .  count($data) . "<br>";

	// Create insert data array
	$insert_data = array();
	$insert_count = 0;
	$i = 0;
	foreach ($data as $row) {
		$insert_data[] = build_insert_string($pair, $row);
		$i += 1;

		// Insert batches
		if ($i == MAX_INSERT_BATCH - 1) {
			$db -> insert($insert_data, 's');

			$insert_count += count($insert_data);
			$insert_data = array();
			$i = 0;
		}
	}

	echo "NUMBER INSERTS - $pair: " . (count($insert_data) + $insert_count) . "<br>";

	// Insert the rest into database
	$db -> insert($insert_data, 's');
}


function already_exists($db, $pair) {
	$existing_pairs = $db->getTagValues('pair');
	foreach ($existing_pairs as $exists) {
		if (strtolower($pair) == $exists) {
			return true;
		}
	}

	return false;
}


function format_num($num) {
	// 8 decimals w/ no commma separators
	return number_format($num, 8, '.', '');
}



function build_insert_string($pair, $row) {
	// Format string w/ Influx line protocol
	$str = 'poloniex,';
	$str .= 'pair=' . strtolower($pair) . " ";
	$str .= "high=" . format_num($row['high']) . ',';
	$str .= "low=" . format_num($row['low']) . ',';
	$str .= "open=" . format_num($row['open']) . ',';
	$str .= "close=" . format_num($row['close']) . ',';
	$str .= "volume=" . format_num($row['volume']) . ',';
	$str .= "quote_volume=" . format_num($row['quoteVolume']) . ',';
	$str .= "weighted_average=" . format_num($row['weightedAverage']);
	$str .= ' ' . $row['date'];

	return $str;
}


function build_insert_string_OLD($pair, $row) {
	// Format string w/ Influx line protocol
	$str = strtolower($pair) . " ";
	$str .= "high=" . format_num($row['high']) . ',';
	$str .= "low=" . format_num($row['low']) . ',';
	$str .= "open=" . format_num($row['open']) . ',';
	$str .= "close=" . format_num($row['close']) . ',';
	$str .= "volume=" . format_num($row['volume']) . ',';
	$str .= "quote_volume=" . format_num($row['quoteVolume']) . ',';
	$str .= "weighted_average=" . format_num($row['weightedAverage']);
	$str .= ' ' . $row['date'];

	return $str;
}


