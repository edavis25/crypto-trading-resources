<?php
require 'poloniex/Poloniex.php';
require 'bittrex/Bittrex.php';

$key = '';
$secret = '';

$polo = new Poloniex($key, $secret);
$btx = new Bittrex();

// Get trading pairs common between exchanges
$btx_pairs = $btx -> get_trading_pairs();
$polo_pairs = $polo -> get_trading_pairs();
$common_pairs = array_values(array_intersect($btx_pairs, $polo_pairs));

$btx_tickers = $btx -> get_formatted_market_summary();
$polo_tickers = $polo -> get_ticker();

// Testing - just do an echo
echo '<ul>';
foreach ($common_pairs as $pair) {
	$polo_bid = $polo_tickers[$pair]['highestBid'];
	$polo_ask = $polo_tickers[$pair]['lowestAsk'];
	$btx_bid = $btx_tickers[$pair]['Bid'];
	$btx_ask = $btx_tickers[$pair]['Ask'];


	if ($polo_ask < $btx_bid) {
		echo $pair . " arb :<br>";
		echo "buy @ polo: $polo_ask & sell @ btx: $btx_bid";
		echo "<br>difference: " . percent_diff($polo_ask, $btx_bid) . "%";
		echo "<br>========================================<br>";
	}

	if ($btx_ask < $polo_bid) {
		echo $pair . " arb :<br>";
		echo "buy @ btx: $btx_ask & sell @ polo: $polo_bid";
		echo "<br>difference: " . percent_diff($polo_ask, $btx_bid) . "%";
		echo "<br>========================================<br>";
	}

}
echo '</ul>';
die;

/*
//var_dump($btx_ticker);
echo number_format($btx_ticker['Ask'], 8) . "<br>";
echo number_format($btx_ticker['Bid'], 8);

//$ask = number_format($btx_ticker['Ask'], 8);
echo "<br><br>";

$polo_ticker = $polo->get_ticker('btc_doge');
echo $polo_ticker['lowestAsk']  . "<br>";
echo $polo_ticker['highestBid'];
*/


function percent_diff($num1, $num2) {
	$diff = (($num1 - $num2) / (($num1 + $num2) / 2)) * 100;
	return abs($diff);
}