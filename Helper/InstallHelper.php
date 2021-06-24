<?php

namespace Avalex\AvalexLegalTexts\Helper;

class InstallHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_API_KEY = 'avalex_privacypolicy/general/apiKey';

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getApiKey($context = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            $context
        );
    }
}
