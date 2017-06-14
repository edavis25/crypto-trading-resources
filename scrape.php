<?php

require 'poloniex/Poloniex.php';
require 'influxdb/InfluxDB.php';

set_time_limit(7200);
ini_set('memory_limit','512M');


$polo = new Poloniex($key, $secret);
$db = new InfluxDB('poloniex', '192.168.0.101');

// Get trading pairs
//$pairs = $polo->get_trading_pairs();

$data = $polo -> get_chart_data('btc_doge', 1405699200, (1405699200+10000), 300);

$insert = array();
foreach ($data as $row) {
	$str = "BTC_DOGE ";
	$str .= "high=" . format_num($row['high']) . ',';
	$str .= "low=" . format_num($row['low']) . ',';
	$str .= "open=" . format_num($row['open']) . ',';
	$str .= "close=" . format_num($row['close']) . ',';
	$str .= "volume=" . format_num($row['volume']) . ',';
	$str .= "quote_volume=" . format_num($row['quoteVolume']) . ',';
	$str .= "weighted_average=" . format_num($row['weightedAverage']);
	$str .= ' ' . $row['date'];

	echo $str . "<br>";
}



function format_num($num) {
	return number_format($num, 8, '.', '');
}
