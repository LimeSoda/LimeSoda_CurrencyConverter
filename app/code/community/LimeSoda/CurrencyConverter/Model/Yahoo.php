<?php

/**
 * Created by PhpStorm.
 * User: pandi
 * Date: 24.05.16
 * Time: 14:34
 */
class LimeSoda_CurrencyConverter_Model_Yahoo extends Mage_Directory_Model_Currency_Import_Abstract
{
    const API_URL = 'http://query.yahooapis.com/v1/public/yql?q=%s&env=store://datatables.org/alltableswithkeys';
    const YQL_QUERY = 'select * from yahoo.finance.xchange where pair in ("%s")';

    /**
     * HTTP client
     *
     * @var Varien_Http_Client
     */
    protected $_httpClient;

    /**
     * @var null|SimpleXMLElement
     */
    protected $apiXmlDocument = null;

    /**
     * @var array
     */
    protected $_messages = [];

    /**
     * LimeSoda_CurrencyConverter_Model_Yahoo constructor.
     */
    public function __construct()
    {
        $this->_httpClient = new Varien_Http_Client();
    }

    /**
     * @return array
     */
    public function fetchRates()
    {
        $this->fetchRatesFromApi();
        return parent::fetchRates();
    }

    protected function fetchRatesFromApi()
    {
        $url = $this->buildApiUrl();
        try {
            $response = $this->_httpClient
                ->setUri($url)
                ->setConfig(array('timeout' => Mage::getStoreConfig('currency/webservicex/timeout')))
                ->request('GET')
                ->getBody();

            $xml = simplexml_load_string($response, null, LIBXML_NOERROR);
            if (!$xml) {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $url);
                return null;
            }
            $this->apiXmlDocument = $xml;
        }
        catch (Exception $e) {
            $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $url);
        }
    }

    /**
     * @return array
     */
    protected function getCurrencyPairs()
    {
        $pairs = [];
        foreach ($this->_getDefaultCurrencyCodes() as $currencyFrom) {
            foreach ($this->_getCurrencyCodes() as $currencyTo) {
                $pairs[] = $this->getCurrencyPairString($currencyFrom, $currencyTo);
            }
        }
        return $pairs;
    }

    /**
     * @param $currencyFrom
     * @param $currencyTo
     * @return string
     */
    protected function getCurrencyPairString($currencyFrom, $currencyTo)
    {
        return $currencyFrom . $currencyTo;
    }

    /**
     * @return string
     */
    protected function buildApiUrl()
    {
        $yqlQuery = urlencode(sprintf(self::YQL_QUERY, implode('","', $this->getCurrencyPairs())));
        return sprintf(self::API_URL, $yqlQuery);
    }

    /**
     * @param string $currencyFrom
     * @param string $currencyTo
     * @return SimpleXMLElement[]
     */
    protected function _convert($currencyFrom, $currencyTo)
    {
        $pairString = $this->getCurrencyPairString($currencyFrom, $currencyTo);

        /** @var SimpleXMLElement $rate */
        foreach ($this->apiXmlDocument->xpath('results/rate') as $rate) {
            if ($this->getNodeAttribute($rate, 'id') == $pairString) {
                return (string)$rate->Rate[0];
            }
        }
    }

    /**
     * @param SimpleXMLElement $node
     * @param $attributeKey
     * @return string
     */
    protected function getNodeAttribute(SimpleXMLElement $node, $attributeKey)
    {
        foreach ($node->attributes() as $key => $value) {
            if ($key == $attributeKey) return (string)$value;
        }
    }
}