<?php

namespace Avalex\AvalexLegalTexts\Helper;

class CurlClientService extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected static $caBundle = "";
    protected static $statusCode = "";
    protected static $errorCode = "";
    protected static $errorMessage = "";
    protected \Magento\Framework\HTTP\ZendClientFactory $zendClientFactory;
    protected \Avalex\AvalexLegalTexts\Logger\Logger $logger;

    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $zendClinetFactory,
        \Avalex\AvalexLegalTexts\Logger\Logger $logger
    ) {
        $this->zendClientFactory = $zendClinetFactory;
        $this->logger = $logger;
    }

    public function execute($url, $isJson = false)
    {
        $client = $this->zendClientFactory->create();
        try {
            $client->setUri($url);
            $client->setMethod(\Zend_Http_Client::GET);
            $client->setConfig(['maxredirects' => 0, 'timeout' => 10]);
            $client->setHeaders(\Zend_Http_Client::CONTENT_TYPE, 'application/json');
            $client->setHeaders("Access-Control-Allow-Origin", "*");
        } catch (\Exception $ex) {
            $this->logger->critical('Error while running cron: '.$ex->getMessage());
        }
        $result = $client->request()->getBody();

        self::$statusCode = $client->request()->getStatus();
        if ($isJson) {
            $result = json_decode($result, true);
        }

        return $result;
    }

    public static function getStatusCode()
    {
        return self::$statusCode;
    }

    public static function getErrorCode()
    {
    }

    public static function getErrorMessage()
    {
    }
}
