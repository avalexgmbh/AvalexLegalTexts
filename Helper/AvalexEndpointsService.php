<?php

namespace Avalex\AvalexLegalTexts\Helper;

class AvalexEndpointsService extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected static $disclaimer = "https://avalex.de/avx-datenschutzerklaerung";
    protected static $imprint = "https://avalex.de/avx-impressum";
    protected static $agb = "https://avalex.de/avx-bedingungen";
    protected static $wrb = "https://avalex.de/avx-widerruf";

    public static function getDisclaimerUrl($apiKey = null, $domain = null)
    {
        $url = self::$disclaimer . "?apikey=" . urlencode($apiKey)."&domain=" . $domain;
        return $url;
    }

    public static function getImprintUrl($apiKey = null, $domain = null)
    {
        $url = self::$imprint . "?apikey=" . urlencode($apiKey)."&domain=" . $domain;
        return $url;
    }

    public static function getAgbUrl($apiKey = null, $domain = null)
    {
        $url = self::$agb . "?apikey=" . urlencode($apiKey)."&domain=" . $domain;
        return $url;
    }

    public static function getWrbUrl($apiKey = null, $domain = null)
    {
        $url = self::$wrb . "?apikey=" . urlencode($apiKey)."&domain=" . $domain;
        return $url;
    }
}
