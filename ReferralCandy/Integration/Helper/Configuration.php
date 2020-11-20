<?php
namespace ReferralCandy\Integration\Helper;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_HELLOWORLD = 'referralcandy/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_HELLOWORLD .'rc_general/'. $code, $storeId);
    }

}
