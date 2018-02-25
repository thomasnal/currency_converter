<?php

/*
 * Represents missing rate error, i.e. rates source doesn't contain the rate.
 */
class MissingCurrencyRateException extends Exception { }


/*
 * EucbRates represents source of rates. It is pluggable into CurrencyConverter
 * class. Interface is class::fetch_rates().
 *
 * Note: should be splitted to a separate file. though this is an exercise only
 */
class EucbRates {
  private $rates;


  public function get_rates()
  {
    return ($rates == NULL ? $this->fetch_rates() : $rates);
  }


  private function fetch_rates()
  {
    $eur_rates = array();

    $url = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";
    $xml = simplexml_load_file($url);

    foreach ($xml->Cube->Cube->Cube as $rate) {
      $currency = $rate['currency'];
      $rates[$currency] = $rate['rate'];
    }

    $rates = array('EUR' => $eur_rates);
  }
}


/*
 * CurrencyConverter is initialized by a rates source. Default source is
 * EucbRates.
 *
 * Note: Amount is rounded to 2 digits. It is not part of requirements but is
 * a basic requirement for work with currency.  Although not test due
 * the missing detailed requirements.
 *
 * - Throws MissingCurrencyRateException on missing rates in the source.
 * - Source is supposed to throw an exception if it can't fetch rates at init
 * time.
 */
class CurrencyConverter {
  public $rates_src;
  public $round_up;
  public $round_down;


  function __construct($rates_src = null, $init = FALSE) {
    $this->rates_src = $rates_src;

    if ($rates_src === null) {
      $this->rates_src = new EucbRates();
    }

    if ($init == TRUE) {
      $this->init();
    }
  }


  function init() {
    $this->rates_src->get_rates();
  }


  function convert($amount, $from, $to) {
    $rates = $this->rates_src->get_rates();

    if (!array_key_exists($from, $rates)) {
      throw new MissingCurrencyRateException("Missing currency rate $from:$to");
    }
    $from_rates = $this->rates_src->get_rates()[$from];

    if (!array_key_exists($to, $from_rates)) {
      throw new MissingCurrencyRateException("Missing currency rate $from:$to");
    }
    $rate = $from_rates[$to];


    return $this->do_conversion($amount, $rate);
  }


  function set_round_up() {
    $this->round_up = TRUE;
    $this->round_down = FALSE;
  }


  function set_round_down() {
    $this->round_up = FALSE;
    $this->round_down = TRUE;
  }


  function set_no_round() {
    $this->round_up = FALSE;
    $this->round_down = FALSE;
  }


  private function do_conversion($amount, $rate) {
    $new_amount = $amount * $rate;

    if ($this->round_up) {
      $new_amount = ceil($new_amount);
    } elseif ($this->round_down) {
      $new_amount = floor($new_amount);
    }

    return round($new_amount, 2);
  }
}
