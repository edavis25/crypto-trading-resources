<?php

    // FINAL TESTED CODE - Created by Compcentral (source: https://pastebin.com/iuezwGRZ)

    // Eric's Note: All functions created by above author EXCEPT:
        // 1. All margin trading functions
        // 2. All lending functions
        // 3. Refactored buy/sell functions
        // 4. get_trade_history()
        // 5. get_chart_data()
        // 6. get_currency_data()
        // 7. get_balances() + get_available_balances()

    // NOTE: currency pairs are reverse of what most exchanges use...
    // For instance, instead of XPM_BTC, use BTC_XPM

    // API Rules: Max 6 calls per second for both public(GET) and trading(POST) APIs

    // API Documentation: https://poloniex.com/support/api/

class poloniex {

    protected $api_key;
    protected $api_secret;
    protected $trading_url = "https://poloniex.com/tradingApi";
    protected $public_url = "https://poloniex.com/public";


    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }


    /************************************************************
     * Public API Functions (market data/trade history/etc.)
     * URL GET requests not mapped to account actions 
     ************************************************************/ 

    // Max 1 year or API throws error & returns max of 50000 trades
    // Returns most recent 200 trades if start/end date arent specified
    public function get_trade_history($pair, $start_date = false, $end_date = false) {
        // Build URL
        $url = $this->public_url . '?command=returnTradeHistory';
        $url .= '&currencyPair=' . strtoupper($pair);

        // Add start and end UNIX timestamps
        if ($start_date && $end_date) {
            $url .= '&start=' . $start_date;
            $url .= '&end=' . $end_date;
        }

        return $this->retrieveJSON($url);
    }


    // Timeperiod = candlestick period in seconds. Default value = 5 mins
    // Valid periods = 300, 900, 1800, 7200, 14400, 86400
    public function get_chart_data($pair, $start_date, $end_date = 9999999999, $time_period = 300) {
        // Build URL
        $url = $this->public_url . '?command=returnChartData';
        $url .= '&currencyPair=' . strtoupper($pair);
        $url .= '&start=' . $start_date;
        $url .= '&end=' . $end_date;
        $url .= '&period=' . $time_period;

        return $this->retrieveJSON($url);
    }


    public function get_order_book($pair) {
        $orders = $this->retrieveJSON($this->public_url.'?command=returnOrderBook&currencyPair='.strtoupper($pair));
        return $orders;
    }


    // Get past 24 hour volume
    public function get_volume() {
        $volume = $this->retrieveJSON($this->public_url.'?command=return24hVolume');
        return $volume;
    }


    // Get ticker info for pair
    public function get_ticker($pair = "ALL") {
        $pair = strtoupper($pair);
        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');

        if ($pair == "ALL"){
            return $prices;
        }
        else {
            $pair = strtoupper($pair);
            
            if (isset($prices[$pair])){
                return $prices[$pair];
            }
            else {
                return array();
            }
        }
    }


    // Get simple list of trading pairs (ex: BTC_ETH, BTC_LTC, BTC_DOGE, etc.)
    public function get_trading_pairs() {
        $tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        return array_keys($tickers);
    }


    // Return currency info (min confirms/full names/disabled or delisted/etc.)
    public function get_currency_data($currency = 'all') {
        $url = $this->public_url . '?command=returnCurrencies';
        $data = $this->retrieveJSON($url);

        if ($currency == 'all') {
            return $data;
        }
        else {
            $currency = strtoupper($currency);
            if (isset($data[$currency])) {
                return $data[$currency];
            }
            else {
                return array();
            }
        }


    }



    /************************************************************
     *  Account Execution Trading Functions (trades/balances/etc.)
     *  POST requests for specific account info & actions
     ************************************************************/ 

    /*
     *  Functions for account info (balances/history/orders/etc.):
     */
    
    // Get current balances for coins (including 0 balances)
    // Note: Does not retrieve balances in margin account
    public function get_balances($currency = 'all') {
        $data = $this->query( 
            array(
                'command' => 'returnBalances'
            )
        );

        if ($currency == 'all') {
            return $data;                       // Return all balances
        }

        $currency = strtoupper($currency);
        if (isset($data[$currency])) {          
            return $data[$currency];            // Return specified balance
        }
        else {
            return array();                     // Currency not found, return empty array
        }
    }


    // Get available balances from all areas of account (non-zero balances)
    // Note: Balances returned as array(3) - keys = 'exchange' 'margin' 'lending'
    public function get_available_balances() {
        $data = array(
            'command' => 'returnAvailableAccountBalances'
        );
        return $this->query($data);
    }


    public function get_open_orders($pair) {        
        return $this->query( 
            array(
                'command' => 'returnOpenOrders',
                'currencyPair' => strtoupper($pair)
            )
        );
    }

    public function get_my_trade_history($pair) {
        return $this->query(
            array(
                'command' => 'returnTradeHistory',
                'currencyPair' => strtoupper($pair)
            )
        );
    }

    public function get_total_btc_balance() {
        $balances = $this->get_balances();
        $prices = $this->get_ticker();

        $tot_btc = 0;

        foreach($balances as $coin => $amount){
            $pair = "BTC_".strtoupper($coin);
            
            // convert coin balances to btc value
            if($amount > 0){
                if($coin != "BTC"){
                    $tot_btc += $amount * $prices[$pair];
                }else{
                    $tot_btc += $amount;
                }
            }

            // process open orders as well
            if($coin != "BTC"){
                $open_orders = $this->get_open_orders($pair);
                
                foreach ($open_orders as $order){
                    if ($order['type'] == 'buy'){
                        $tot_btc += $order['total'];
                    }
                    elseif ($order['type'] == 'sell'){
                        $tot_btc += $order['amount'] * $prices[$pair];
                    }
                }
            }
        }

        return $tot_btc;
    }


    /*
     *  Functions for standard trade execution:
     */

    public function buy($pair, $amount, $rate) {
        return $this -> buy_or_sell($pair, $amount, $rate, 'buy');
    }

    
    public function sell($pair, $amount, $rate) {
        return $this -> buy_or_sell($pair, $amount, $rate, 'sell');
    }


    public function cancel_order($pair, $order_number) {
        return $this->query( 
            array(
                'command' => 'cancelOrder',  
                'currencyPair' => strtoupper($pair),
                'orderNumber' => $order_number
            )
        );
    }


    /*
     *  Functions for margin trading
     */

    // Get current margin positions info
    public function get_margin_position($pair = "all") {
        $data = array(
            'command' => 'getMarginPosition',
            'currencyPair' => strtoupper($pair)
        );
        return $this->query($data); 
    }

    // Get meta summary of margin account (fees/borrowed value/etc.)
    public function get_margin_account_summary() {
        $data = array(
            'command' => 'returnMarginAccountSummary'
        );
        return $this->query($data);
    }

    // Close a margin position (returns success even if no position is currently open)
    public function close_margin_position($pair) {
        $data = array(
            'command' => 'closeMarginPosition',
            'currencyPair' => strtoupper($pair)
        );
        return $this->query($data);
    }

    // Default max lending rate = 2%
    public function margin_buy($pair, $amount, $rate, $max_lending_rate = 0.02) {
        return $this -> buy_or_sell($pair, $amount, $rate, 'marginBuy', $max_lending_rate);
    }

    // Default max lending rate = 2%
    public function margin_sell($pair, $amount, $rate, $max_lending_rate = 0.02) {
        return $this -> buy_or_sell($pair, $amount, $rate, 'marginSell', $max_lending_rate);
    }


    /*
     *  Functions for lending
     */

    // Default duration = 2 days
    // Default loan rate = 2%
    public function create_loan_offer($currency, $amount, $rate = 0.02, $duration = 2, $auto_renew = false) {
        $data = array(
            'command' => 'createLoanOffer',
            'currency' => strtoupper($currency),
            'amount' => $amount,
            'duration' => $duration,
            'autoRenew' => $auto_renew,
            'lendingRate' => $rate
        );
        return $this->query($data);
    }


    // Get details for open loans
    public function get_open_loan_offers($currency = 'all') {
        $data = array(
            'command' => 'returnOpenLoanOffers'
        );
        $json = $this->query($data);

        if ($currency == 'all') {
            return $json;
        }

        $currency = strtoupper($currency);
        if (isset($json[$currency])) {
            return $json[$currency];
        }
        return array();      // Return empty array currency not found
    }


    public function get_active_loans() {
        $data = array(
            'command' => 'returnActiveLoans'
        );
        return $this->query($data);
    }


    public function cancel_loan_offer($order_number) {
        $data = array(
            'command' => 'cancelLoanOffer',
            'orderNumber' => $order_number
        );
        return $this->query($data);
    }


    // API only allows toggling of autorenew - it doesn't support setting
    // true/false so make sure you know its current status before toggling.
    // Returns new autorenew status inside 'message' key as binary boolean (0/1)
    // Note: Only works on active loans (fails on loans still open)
    public function toggle_autorenew($order_number) {
        $data = array(
            'command' => 'toggleAutoRenew',
            'orderNumber' => $order_number
        );
        return $this->query($data);
    }


    /*
     *  Functions for addresses & withdraws
     */
    public function withdraw($currency, $amount, $address) {
        return $this->query( 
            array(
                'command' => 'withdraw',    
                'currency' => strtoupper($currency),                
                'amount' => $amount,
                'address' => $address
            )
        );
    }


    public function get_deposit_address($currency = 'all') {
        $data = array(
            'command' => 'returnDepositAddresses'
        );
        $json = $this->query($data);

        if ($currency == 'all') {
            return $json;
        }

        $currency = strtoupper($currency);
        if (isset($json[$currency])) {
            return $json[$currency];
        }
        return array();         // Currency not found return empty array
    }


    public function generate_deposit_address($currency) {
        $data = array(
            'command' => 'generateNewAddress',
            'currency' => strtoupper($currency)
        );
        return $this->query($data);
    }


    public function transfer_balance($currency, $amount, $origin_acct, $dest_acct) {
        $data = array(
            'command' => 'transferBalance',
            'currency' => strtoupper($currency),
            'amount' => $amount,
            'fromAccount' => $origin_acct,
            'toAccount' => $dest_acct
        );
        return $this->query($data);
    }


    /************************************************************
     *  Helper functions
     ************************************************************/

    private function buy_or_sell($pair, $amount, $rate, $type, $max_lending_rate = 0.02) {
        $data = array(
            'command' => $type,
            'currencyPair' => strtoupper($pair),
            'rate' => $rate,
            'amount' => $amount
        );

        if ($type == 'marginBuy' || $type == 'marginSell') {
            $data['lendingRate'] = $max_lending_rate;
        }

        return $this->query($data);
    }


    // GET request to retrieve JSON from given URL
    protected function retrieveJSON($URL) {
        $opts = array('http' =>
            array(
                'method'  => 'GET',
                'timeout' => 10 
                )
            );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        $json = json_decode($feed, true);
        return $json;
    }


    // POST request for executing trade functions
    private function query(array $req = array()) {
        
        // API settings
        $key = $this->api_key;
        $secret = $this->api_secret;

        // generate a nonce to avoid problems with 32bit systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
        $sign = hash_hmac('sha512', $post_data, $secret);

        // generate the extra headers
        $headers = array(
            'Key: '.$key,
            'Sign: '.$sign,
        );

        // curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 
                'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
                );
        }
        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);

        if ($res === false) {
            throw new Exception('Curl error: '.curl_error($ch));
        }

        $dec = json_decode($res, true);

        if (!$dec){
            //throw new Exception('Invalid data: '.$res);
            return false;
        }
        else{
            return $dec;
        }
    }

}
