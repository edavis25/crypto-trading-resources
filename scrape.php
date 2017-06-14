<?php

require 'poloniex/Poloniex.php';
require 'influxdb/InfluxDB.php';

const FIVE_MINUTES = 300;
const TWO_HOURS = 7200;
const MAX_INSERT_BATCH = 6000;

set_time_limit(TWO_HOURS);
ini_set('memory_limit','512M');



$polo = new Poloniex($key, $secret);
$db = new InfluxDB('poloniex', '192.168.0.101');

$start = time();
echo "START TIME: " . $start . "<br>";

// Get trading pairs
//$pairs = $polo->get_trading_pairs();

$data = $polo -> get_chart_data('btc_doge', 0000000000, 9999999999, FIVE_MINUTES);

echo "NUMBER RESULTS: " . count($data) . "<br>";

// Create insert data array
$insert_data = array();
$insert_count = 0;
$i = 0;
foreach ($data as $row) {
	// Format string w/ Influx line protocol
	$str = "BTC_DOGE ";
	$str .= "high=" . format_num($row['high']) . ',';
	$str .= "low=" . format_num($row['low']) . ',';
	$str .= "open=" . format_num($row['open']) . ',';
	$str .= "close=" . format_num($row['close']) . ',';
	$str .= "volume=" . format_num($row['volume']) . ',';
	$str .= "quote_volume=" . format_num($row['quoteVolume']) . ',';
	$str .= "weighted_average=" . format_num($row['weightedAverage']);
	$str .= ' ' . $row['date'];

	$insert_data[] = $str;
	$i += 1;

	// Insert batches
	if ($i == MAX_INSERT_BATCH - 1) {
		$db -> insert($insert_data, 's');

		$insert_count += count($insert_data);
		$insert_data = array();
		$i = 0;
	}
}

echo "NUMBER INSERTS: " . (count($insert_data) + $insert_count) . "<br>";

// Insert the rest into database
$db -> insert($insert_data, 's');

echo "DONE!<br>";
$end = time();
echo "END TIME: " . $end . "<br>";
echo "ELAPSED: " . ($end - $start);

function format_num($num) {
	return number_format($num, 8, '.', '');
}
