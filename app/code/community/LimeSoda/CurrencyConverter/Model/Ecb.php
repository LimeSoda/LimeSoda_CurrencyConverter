<?php

class LimeSoda_CurrencyConverter_Model_Ecb extends Mage_Directory_Model_Currency_Import_Abstract
{
    /**
     * @var string
     */
	protected $_url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * @var array
     */
	protected $_messages = array();

    /**
     * @var array
     */
	protected $_rates = array();

    /**
     * HTTP client
     *
     * @var Varien_Http_Client
     */
    protected $_httpClient;

    public function __construct()
    {
        $this->_httpClient = new Varien_Http_Client();
    }

    /**
     * @param int $retry
     * @return null|SimpleXMLElement
     */
    protected function _fetchRatesFromService($retry = 0)
    {
        $xml = null;

        try {
            $timeout = Mage::getStoreConfig('currency/ls_currencyconverter/timeout');
            $response = $this->_httpClient
                ->setUri($this->_url)
                ->setConfig(array('timeout' => $timeout))
                ->request('GET')
                ->getBody();

            $xml = simplexml_load_string($response, null, LIBXML_NOERROR);
        }
        catch (Exception $e) {
            if ($retry == 0) {
                $this->_fetchRatesFromService(1);
            }
        }

        return $xml;
    }

    /**
     * @param string $currencyFrom
     * @param string $currencyTo
     * @param int $retry
     * @return float|void
     */
	protected function _convert($currencyFrom, $currencyTo, $retry = 0)
    {
        $xml = $this->_fetchRatesFromService($retry);

        if (!$xml) {
            $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $this->_url);
            return;
        }

        $this->_rates['EUR'] = 1;

        foreach ($xml->Cube->Cube->Cube as $rate) {
            $this->_rates[(string) $rate['currency']] = floatval($rate['rate']);
        }

        return (float) 1 / $this->_rates[$currencyFrom] * $this->_rates[$currencyTo];
	}
}
