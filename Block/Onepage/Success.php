<?php
namespace ReferralCandy\Integration\Block\Onepage;

use ReferralCandy\Integration\Helper\Configuration;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /** Production ReferralCandy purchase host; see etc/config.xml. */
    const DEFAULT_PURCHASE_DOMAIN = 'go.referralcandy.com';

    protected $_locale;
    protected $_escaper;
    protected $_configurationHelper;
    protected $_enabled;
    protected $_appId;
    protected $_apiSecretKey;
    protected $_order;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Locale\ResolverInterface $locale,
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
        $this->_apiSecretKey            = $this->_configurationHelper->getApiSecretKey();
        $this->_order                   = $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * Check whether the purchase script should be rendered and triggered
     *
     * The order timestamp is part of the signature AND a separate data attribute, so a
     * missing one is not a cosmetic gap: the snippet would render, the signature would be
     * computed over an empty string, and ReferralCandy would reject every purchase while
     * the storefront looked healthy. Render nothing instead of something silently broken.
     */
    public function shouldTriggerJsPurchase()
    {
        return ($this->_enabled
                && !empty($this->_appId)
                && !empty($this->_apiSecretKey)
                && isset($this->_order)
                && $this->getOrderTimestamp() !== null);
    }

    /**
     * Host the storefront tracking script is loaded from.
     *
     * Defaults to production via etc/config.xml. The path is deliberately absent from
     * etc/adminhtml/system.xml, so `bin/magento config:set` will not accept it; a test
     * store overrides it in app/etc/env.php instead (see etc/config.xml).
     * Returning the default rather than an empty string matters: a blank host would
     * build a same-origin script URL that 404s on the merchant's own storefront.
     */
    public function getPurchaseDomain()
    {
        $domain = $this->_configurationHelper->getGeneralConfig('purchase_domain');

        return !empty($domain) ? trim($domain) : self::DEFAULT_PURCHASE_DOMAIN;
    }

    /**
     * Get customer's first name, or generate one from their email
     */
    private function getOrGenerateFirstName()
    {
        $firstName = $this->_order->getCustomerFirstName();
        if (!empty($firstName)) {
            return $firstName;
        }

        // A guest order can carry no email at all, and PHP 8.1 deprecates passing null
        // to explode(), so coalesce before splitting rather than after.
        $email = (string) $this->_order->getCustomerEmail();
        $emailWithoutDomain = explode('@', $email)[0];
        $emailWithoutTag = explode('+', $emailWithoutDomain)[0];
        return $emailWithoutTag;
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
            if (array_key_exists($locale, $localeMapping)) {
                $locale = $localeMapping[$locale];
            } else {
                $locale = strstr($locale, '_', true); // Example: en_US > en
            }
        }

        return $locale;
    }

    /**
     * The order's creation time as a Unix timestamp.
     *
     * `sales_order.created_at` is a zone-less UTC 'Y-m-d H:i:s' string. `strtotime` would
     * parse it in the PHP process timezone, so on any store whose `date.timezone` is not
     * UTC the timestamp is shifted by the local offset — and because that value is also
     * part of the signature, the signature still verifies while the purchase time is
     * wrong. Neither side reports anything. Parse the zone explicitly instead.
     *
     * Returns null when there is nothing parseable to return; shouldTriggerJsPurchase()
     * treats that as a reason to render no snippet at all.
     */
    private function getOrderTimestamp()
    {
        $createdAt = $this->_order->getCreatedAt();

        if (empty($createdAt)) {
            return null;
        }

        try {
            $utc = new \DateTime($createdAt, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return null;
        }

        return $utc->getTimestamp();
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
            'orderTimestamp' => $this->getOrderTimestamp()
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
