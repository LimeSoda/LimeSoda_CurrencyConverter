<?php

class LimeSoda_CurrencyConverter_Model_Ecb extends Mage_Directory_Model_Currency_Import_Abstract {
	protected $_url = 'http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
	protected $_messages = array();
	protected $_rates = array();

	protected function _convert($currencyFrom, $currencyTo, $retry = 0) {
		$xml = simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

		try {
			$this -> _rates["EUR"] = 1;
			foreach ($xml->Cube->Cube->Cube as $rate) {
				$this -> _rates[(string)$rate["currency"]] = floatval($rate["rate"]);
			}

			if (!$xml) {
				$this -> _messages[] = Mage::helper('directory') -> __('Cannot retrieve rate from %s.', $this -> _url);
				return null;
			}
			return (float)1 / $this -> _rates[$currencyFrom] * $this -> _rates[$currencyTo];
		} catch (Exception $e) {
			$this -> _messages[] = Mage::helper('directory') -> __('Cannot retrieve rate from %s.', $this -> _url);
		}
	}

}
