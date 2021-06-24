<?php

namespace Avalex\AvalexLegalTexts\Helper;

use Avalex\AvalexLegalTexts\Model\AvalexLogFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Variable\Model\VariableFactory;
use Avalex\AvalexLegalTexts\Helper\CurlClientService;
use Avalex\AvalexLegalTexts\Helper\AvalexEndpointsService;

class AvalexApiService extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_API_KEY = 'avalex_privacypolicy/general/apiKey';

    protected static $checkIntervalSeconds = (5.5 * 60 * 60);
    protected static $cacheMaxVersions = 5;

    /**
     * @var storeManager $storeManager
     */
    protected $storeManager;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Avalex\AvalexLegalTexts\Model\AvalexLogFactory
     */
    protected $logModelFactory;

    /**
     * @var \Magento\Variable\Model\VariableFactory $varFactory
     */
    protected $varFactory;

    /**
     * @var object $apiKeyHelper
     */
    private $apiKeyHelper;

    /**
     * @var string $apiKey
     */
    private $apiKey;

    /**
     * @var \Magento\Store\Model\StoreRepository list element $store
     */
    private $store;

    /**
     * @var \Avalex\AvalexLegalTexts\Logger\Logger $logger
     */
    protected $logger;

    /**
     * @var boolean $debug
     */
    private $debug;

    /**
     * @var string $domain
     */
    private $domain;

    public function __construct(
        StoreManagerInterface $storeManager,
        VariableFactory $varFactory,
        ScopeConfigInterface $scopeConfig,
        ApiKeyHelper $apiKeyHelper,
        AvalexLogFactory $logModelFactory,
        \Avalex\AvalexLegalTexts\Logger\Logger $logger,
        CurlClientService $curlClientService,
        AvalexEndpointsService $avalexEndpointsService
    ) {
        $this->storeManager = $storeManager;
        $this->varFactory = $varFactory;
        $this->scopeConfig = $scopeConfig;
        $this->apiKeyHelper = $apiKeyHelper;
        $this->logModelFactory = $logModelFactory;
        $this->logger = $logger;
        $this->curlClientService = $curlClientService;
        $this->avalexEndpointsService = $avalexEndpointsService;
        $this->debug = false;
    }

    public function getLatestVersion($legalContentType)
    {
        if ($this->debug) {
            $this->logger->info("getLatestVersion: Begin");
        }
        if ($this->debug) {
            $this->logger->info("ContentType: " . $legalContentType);
        }

        $logModel = $this->logModelFactory->create();
        $resultObj = $logModel
                ->getCollection()
                ->addFieldToFilter('store_code', $this->store->getCode())
                ->addFieldToFilter('type', $legalContentType)
                ->setOrder('changed', 'DESC')
                ->setPageSize(1)
                ->setCurPage(1);
        if ($this->debug) {
            $this->logger->info("getLatestVersion: " . $resultObj->count() . " versions found");
        }

        if ($resultObj->count() > 0) {
            $resultArr = $resultObj->getData();
            if ($this->debug) {
                $this->logger->info("getLatestVersion: Array ->" . print_r($resultArr, true));
            }
            if ($this->debug) {
                $this->logger->info("getLatestVersion: Date -> " . $resultArr[0]['changed']);
            }
            return $resultArr[0];
        } else {
            return false;
        }
    }

    public function fetchNewVersion($legalContentType)
    {
        if ($this->debug) {
            $this->logger->info("fetchNewVersion: Begin");
        }
        if ($this->debug) {
            $this->logger->info("ContentType: " . $legalContentType);
        }

        switch ($legalContentType) {
            case "datenschutz":
                $result = $this->curlClientService->execute($this->avalexEndpointsService->getDisclaimerUrl($this->apiKey, $this->domain));
                if ($this->debug) {
                    $this->logger->info("Called URL: " . $this->avalexEndpointsService->getDisclaimerUrl($this->apiKey, $this->domain));
                }
                $var_code = 'avalex';
                $var_title = 'avalex Datenschutzerklärung';
                break;

            case "imprint":
                $result = $this->curlClientService->execute($this->avalexEndpointsService->getImprintUrl($this->apiKey, $this->domain));
                if ($this->debug) {
                    $this->logger->info("Called URL: " . $this->avalexEndpointsService->getImprintUrl($this->apiKey, $this->domain));
                }
                $var_code = 'avalex_de_impressum';
                $var_title = 'avalex Impressum';
                break;

            case "agb":
                $result = $this->curlClientService->execute($this->avalexEndpointsService->getAgbUrl($this->apiKey, $this->domain));
                if ($this->debug) {
                    $this->logger->info("Called URL: " . $this->avalexEndpointsService->getAgbUrl($this->apiKey, $this->domain));
                }
                $var_code = 'avalex_de_agb';
                $var_title = 'avalex Allgemeine Geschäftsbedingungen';
                break;

            case "wrb":
                $result = $this->curlClientService->execute($this->avalexEndpointsService->getWrbUrl($this->apiKey, $this->domain));
                if ($this->debug) {
                    $this->logger->info("Called URL: " . $this->avalexEndpointsService->getWrbUrl($this->apiKey, $this->domain));
                }
                $var_code = 'avalex_de_widerrufsbelehrung';
                $var_title = 'avalex Widerrufsbelehrung';
                break;
        }

        if ($this->curlClientService->getStatusCode() == 200 && strlen($result) > 200) {
            $this->logger->info("fetchNewVersion: Fetched successfully");

            // Save in database
            $datetime = new \DateTime();
            $newObj = $this->logModelFactory->create();
            $newObj->setData('html', $result);
            $newObj->setData('type', $legalContentType);
            $newObj->setData('store_code', $this->store->getCode());
            $newObj->setData('changed', $datetime->format('Y-m-d H:i:s'));
            $newObj->save();

            // Refresh or create custom variable 'avalex #store_id# #store_code#' for inserting in pages
            $varModel = $this->varFactory->create();
            $varModel->setStoreId($this->store->getId());
            $varObj = $varModel->loadByCode($var_code);

            $varObj->setStoreId($this->store->getId());
            $varObj->setData('code', $var_code);
            $varObj->setData('name', $var_title);
            $varObj->setData('html_value', $result);
            $varObj->save();

            if ($legalContentType === 'datenschutz') {
                $varModel = $this->varFactory->create();
                $varModel->setStoreId($this->store->getId());
                $varObj = $varModel->loadByCode('avalex_de_datenschutz');

                $varObj->setStoreId($this->store->getId());
                $varObj->setData('code', 'avalex_de_datenschutz');
                $varObj->setData('name', $var_title);
                $varObj->setData('html_value', $result);
                $varObj->save();
            }

            $this->logger->info('avalex custom variable ´' . $var_code . '` renewed');

            // Delete old versions, keep 5 at minimum
            $keepVersions = (int)self::$cacheMaxVersions;
            if ($keepVersions < 5) {
                $keepVersions = 5;
            }
            $keepIds = [];

            $keepModel = $this->logModelFactory->create();
            $resultObj = $keepModel
                    ->getCollection()
                    ->addFieldToFilter('store_code', $this->store->getCode())
                    ->addFieldToFilter('type', $legalContentType)
                    ->setOrder('changed', 'DESC')
                    ->setPageSize($keepVersions)
                    ->setCurPage(1);
            $resultArr = $resultObj->getData();
            if ($resultArr !== false && count($resultArr) > 0) {
                foreach ($resultArr as $resultRow) {
                    $keepIds[] = $resultRow['version_id'];
                }
            }
            $this->logger->info("keepIds: " . print_r($keepIds, true));
            if (count($keepIds) >= 5) {
                $deleteModel = $this->logModelFactory->create();
                $deleteObj = $deleteModel
                        ->getCollection()
                        ->addFieldToFilter('store_code', $this->store->getCode())
                        ->addFieldToFilter('type', $legalContentType)
                        ->addFieldToFilter('version_id', ['nin' => $keepIds]);
                $deleteArr = $deleteObj->getData();
                if ($deleteArr !== false && count($deleteArr) > 0) {
                    foreach ($deleteArr as $deleteRow) {
                        $this->logModelFactory->create()->load($deleteRow['version_id'])->delete();
                        $this->logger->info("deleted old version ID: " . $deleteRow['version_id'] . " in store " . $this->store->getCode());
                    }
                } else {
                    $this->logger->info("no old versions to delete");
                }
            }

            // save log
            $this->logger->info("fetchNewVersion: Finished");
            return true;
        } else {
            $this->logger->warn("fetchNewVersion: Unknown error");
        }

        return false;
    }

    public function runUpdateCheck($store, $legalContentType = "datenschutz")
    {
        $updated = false;

        $this->store = $store;

        // set current store scope
        $this->storeManager->setCurrentStore($this->store->getCode());
        $domain = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $domain = str_replace('http://', '', $domain);
        $domain = str_replace('https://', '', $domain);

        $this->domain = str_replace("/", "", $domain);
        $this->apiKey = $this->apiKeyHelper->getApiKey();

        if ($this->apiKey !== "") {
            $this->logger->info("runUpdateCheck: Begin");
            $this->logger->info("apiKey: " . $this->apiKeyHelper->getApiKey());
            $this->logger->info("domain: " . $this->domain);

            $lastestVersion = $this->getLatestVersion($legalContentType);
            if ($lastestVersion === false) {
                $lastestVersionTimestamp = 0;
            } else {
                $lastestVersionTimestamp = strtotime($lastestVersion['changed']);
            }
            if ($this->debug) {
                $this->logger->info("lastestVersionTimestamp: " . $lastestVersionTimestamp);
            }
            $timeNow = new \DateTime();
            if ($this->debug) {
                $this->logger->info("timeNow: " . $timeNow->getTimestamp());
            }
            if ($this->debug) {
                $this->logger->info("checkIntervalSeconds: " . self::$checkIntervalSeconds);
            }

            if ($lastestVersion === false || $timeNow->getTimestamp() - $lastestVersionTimestamp > self::$checkIntervalSeconds) {
                $updated = $this->fetchNewVersion($legalContentType);
            }
        } else {
            $this->logger->error("Abort! No API-Key set!");
        }

        return $updated;
    }
}
