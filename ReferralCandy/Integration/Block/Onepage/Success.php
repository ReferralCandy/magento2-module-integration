<?php
namespace ReferralCandy\Integration\Block\Onepage;

use ReferralCandy\Integration\Helper\Configuration;
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Locale\Resolver $locale,
        Configuration $configurationHelper,
        array $data = []
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->_locale                  = $locale;
        $this->_configurationHelper     = $configurationHelper;
        $this->_enabled                 = boolval($this->_configurationHelper->getGeneralConfig('enabled'));
        $this->_appId                   = $this->_configurationHelper->getGeneralConfig('app_id');
        $this->_apiAccessId             = $this->_configurationHelper->getGeneralConfig('api_access_id');
        $this->_apiSecretKey            = $this->_configurationHelper->getGeneralConfig('api_secret_key');
        $this->_order                   = $this->_checkoutSession->getLastRealOrder();
    }

    private function shouldTriggerJsPurchase()
    {
        return $this->_enabled && !empty($this->_appId) && !empty($this->_apiSecretKey);
    }

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

    public function displayLocaleCode() {
        $localeCode = $this->_locale->getLocale();
        echo "LOCALE: $localeCode";
    }

    private function generateLocaleData()
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

            return "data-locale='$locale'";
        }

        return ''; // Do not add data-locale to assign campaign's default
    }

    public function generateRcDiv()
    {
        if ($this->shouldTriggerJsPurchase() === true) {
            $id                     = $this->_order->getId();
            $firstName              = $this->getOrGenerateFirstName();
            $lastName               = $this->_order->getCustomerLastName();
            $email                  = $this->_order->getCustomerEmail();
            $subtotal               = $this->_order->getSubtotal();
            $generatedLocaleData    = $this->generateLocaleData();
            $currencyCode           = $this->_order->getOrderCurrencyCode();
            $orderTimestamp         = strtotime($this->_order->getCreatedAt());

            $signatureParams = [
                $email,
                $firstName,
                $subtotal,
                $orderTimestamp,
                $this->_apiSecretKey
            ];
            $signature = md5(join(',', $signatureParams));

            echo "<div id='refcandy-mint' data-app-id='$this->_appId' data-fname='$firstName' data-lname='$lastName' data-email='$email' data-amount='$subtotal' $generatedLocaleData data-currency='$currencyCode' data-timestamp='$orderTimestamp' data-external-reference-id='$id' data-signature='$signature' ></div>";
        }
    }

    public  function generateRcJs()
    {
        if ($this->shouldTriggerJsPurchase() === true) {
            echo "<script>(function(e){var t,n,r,i,s,o,u,a,f,l,c,h,p,d,v;z='script';l='refcandy-purchase-js';c='refcandy-mint';p='go.referralcandy.com/purchase/';t='data-app-id';r={email:'a',fname:'b',lname:'c',amount:'d',currency:'e','accepts-marketing':'f',timestamp:'g','referral-code':'h',locale:'i','external-reference-id':'k',signature:'ab'};i=e.getElementsByTagName(z)[0];s=function(e,t){if(t){return''+e+'='+encodeURIComponent(t)}else{return''}};d=function(e){return''+p+h.getAttribute(t)+'.js?aa=75&'};if(!e.getElementById(l)){h=e.getElementById(c);if(h){o=e.createElement(z);o.id=l;a=function(){var e;e=[];for(n in r){u=r[n];v=h.getAttribute('data-'+n);e.push(s(u,v))}return e}();o.src='//'+d(h.getAttribute(t))+a.join('&');return i.parentNode.insertBefore(o,i)}}})(document);</script>";
        }
    }
  }
