<?php

namespace Avalex\AvalexLegalTexts\Model;

use \Magento\Framework\Model\AbstractModel;

class AvalexLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Avalex\AvalexLegalTexts\Model\ResourceModel\AvalexLog');
    }
}
