<?php

namespace Avalex\AvalexLegalTexts\Cron;

use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface ;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreRepository;
use Avalex\AvalexLegalTexts\Helper\AvalexApiService;
use Avalex\AvalexLegalTexts\Model\AvalexLogFactory;

class PrivacyPolicyUpdate
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Store\Model\StoreRepository $storeRepository
     */
    protected $storeRepository;

    /**
     * @var \Avalex\AvalexLegalTexts\Helper\AvalexApiService $apiService
     */
    protected $apiService;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     */
    protected $cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     */
    protected $cacheFrontendPool;

    /**
     * @var \Avalex\AvalexLegalTexts\Logger\Logger $logger
     */
    protected $logger;

    /**
     * @var \Avalex\AvalexLegalTexts\Model\AvalexLogFactory
     */
    protected $avalexLogFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        StoreRepository $storeRepository,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        AvalexApiService $apiService,
        \Avalex\AvalexLegalTexts\Logger\Logger $logger,
        AvalexLogFactory $avalexLogFactory
    ) {
        $this->storeManager = $storeManager;
        $this->storeRepository = $storeRepository;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->apiService = $apiService;
        $this->avalexLogFactory = $avalexLogFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        $availableLegalContentTypes = ["datenschutz", "imprint", "agb", "wrb"];

        $this->logger->info("---------------------------------------------");
        $this->logger->info("Avalex CRON");

        $avalexLog = $this->avalexLogFactory->create();
        $collection = $avalexLog->getCollection();

        //$this->logger->info(print_r($collection,1));
        $this->logger->info("---------------------------------------------");

        $updated = false;

        // loop through all stores
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            if ($store->isActive()) {

                foreach ($availableLegalContentTypes as $type) {
                    $this->logger->info("---------------------------------------------");
                    $this->logger->info("Store-ID: ".$store->getId().": ".$store->getCode());
                    $this->logger->info("LegalContentType: ". $type);

                    // call update check
                    $update_done = $this->apiService->runUpdateCheck($store, $type);
                    $this->logger->info("AVALEX: update_done ".print_r($update_done, 1));
                    $this->logger->info("---------------------------------------------");

                    if ($update_done) {
                        $types = ['config','block_html','full_page','translate'];

                        foreach ($types as $cachetype) {
                            $this->cacheTypeList->cleanType($cachetype);
                        }
                        foreach ($this->cacheFrontendPool as $cacheFrontend) {
                            $cacheFrontend->getBackend()->clean();
                        }
                    }
                }
            }
        }
    }
}
