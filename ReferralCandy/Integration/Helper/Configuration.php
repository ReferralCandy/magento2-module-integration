<?php
/**
 * Helper file for fetching ReferralCandy account keys
 */

namespace ReferralCandy\Integration\Helper;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XLM_PATH_REFERRALCANDY = 'referralcandy/';

    /**
     * Helper function to get config values
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Helper function to get config values from general configuration
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(
            self::XLM_PATH_REFERRALCANDY .'rc_general/'. $code,
            $storeId
        );
    }
}
