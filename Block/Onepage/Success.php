<?php
namespace ReferralCandy\MagentoIntegration\Block\Onepage;

use ReferralCandy\MagentoIntegration\Helper\Configuration;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
    protected $_locale;
    protected $_escaper;
    protected $_configurationHelper;
    protected $_enabled;
    protected $_appId;
    protected $_apiAccessId;
    protected $_apiSecretKey;
    protected $_order;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Locale\Resolver $locale,
        \Magento\Framework\Escaper $escaper,
        Configuration $configurationHelper,
        array $data = []
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->_locale                  = $locale;
        $this->_escaper                 = $escaper;
        $this->_configurationHelper     = $configurationHelper;
        $this->_enabled                 = boolval($this->_configurationHelper->getGeneralConfig('enabled'));
        $this->_appId                   = $this->_configurationHelper->getGeneralConfig('app_id');
        $this->_apiAccessId             = $this->_configurationHelper->getGeneralConfig('api_access_id');
        $this->_apiSecretKey            = $this->_configurationHelper->getGeneralConfig('api_secret_key');
        $this->_order                   = $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Check whether the purchase script should be rendered and triggered
     */
    public function shouldTriggerJsPurchase()
    {
        return $this->_enabled && !empty($this->_appId) && !empty($this->_apiSecretKey);
    }

    /**
     * Get customer's first name, or generate one from their email
     */
    private function getOrGenerateFirstName()
    {
        if (!empty($this->_order->getCustomerFirstName())) {
            return $this->_order->getCustomerFirstName();
        } else {
            $emailWithoutDomain = explode('@', $this->_order->getCustomerEmail())[0];
            $emailWithoutTag = explode('+', $emailWithoutDomain)[0];
            return $emailWithoutTag;
        }
    }

    /**
     * Get store's locale and convert it to ReferralCandy mapping if necessary
     */
    private function getStoreLocale()
    {
        $localeMapping = [ // Map to ReferralCandy format
            'zh_Hans_CN'    => 'zh-CN',
            'zh_Hant_HK'    => 'zh-HK',
            'zh_Hant_TW'    => 'zh-TW',
            'pt_BR'         => 'pt-BR'
        ];

        $locale = $this->_locale->getLocale();

        if (!empty($locale)) {
            if (key_exists($locale, $localeMapping)) {
                $locale = $localeMapping[$locale];
            } else {
                $locale = strstr($locale, '_', true); // Example: en_US > en
            }
        }

        return $locale;
    }

    /**
     * Generate required div data for the purchase script
     */
    public function divData()
    {
        $divData = [
            'appId'          => $this->_appId,
            'orderId'        => $this->_order->getId(),
            'firstName'      => $this->getOrGenerateFirstName(),
            'lastName'       => $this->_order->getCustomerLastName(),
            'email'          => $this->_order->getCustomerEmail(),
            'subtotal'       => $this->_order->getSubtotal(),
            'locale'         => $this->getStoreLocale(),
            'currencyCode'   => $this->_order->getOrderCurrencyCode(),
            'orderTimestamp' => strtotime($this->_order->getCreatedAt())
        ];

        $signatureParams = [
            $divData['email'],
            $divData['firstName'],
            $divData['subtotal'],
            $divData['orderTimestamp'],
            $this->_apiSecretKey
        ];

        /**
         * MD5 is used by ReferralCandy to generate a signature
         */
        $divData['signature'] = md5(join(',', $signatureParams));

        return $divData;
    }
}
