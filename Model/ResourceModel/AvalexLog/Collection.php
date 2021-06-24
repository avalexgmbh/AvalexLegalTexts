<?php

namespace Avalex\AvalexLegalTexts\Model\ResourceModel\AvalexLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'version_id';
    protected $_eventPrefix = 'avalex_privacy_policy_avalex_log_collection';
    protected $_eventObject = 'avalex_log_collection';

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Avalex\AvalexLegalTexts\Model\AvalexLog',
            'Avalex\AvalexLegalTexts\Model\ResourceModel\AvalexLog'
        );
    }
}
