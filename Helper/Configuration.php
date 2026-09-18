<?php
/**
 * Helper file for fetching ReferralCandy account keys
 */

namespace ReferralCandy\Integration\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XLM_PATH_REFERRALCANDY = 'referralcandy/';

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;
    }

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

    /**
     * The API secret, decrypted.
     *
     * Before 1.0.2 this value was stored in `core_config_data` in the clear, so every
     * store that has not re-saved its configuration still holds a plaintext row.
     * `EncryptorInterface::decrypt` returns an empty string for anything that is not in
     * Magento's `<key>:<cipher>:<base64>` format, which would silently blank the secret
     * — and a blank secret means a signature every purchase fails on, forever, with no
     * merchant-visible signal. So fall back to the stored value when decryption yields
     * nothing: the merchant keeps working, and their next save re-writes it encrypted.
     */
    public function getApiSecretKey($storeId = null)
    {
        $stored = $this->getGeneralConfig('api_secret_key', $storeId);

        if (empty($stored)) {
            return $stored;
        }

        // Only decrypt something that actually carries Magento's envelope
        // (`<keyVersion>:<cipherVersion>:<base64>`). `Encryptor::decrypt` does NOT reject a
        // value without colons: it treats it as the oldest supported format and
        // Blowfish-decrypts it under key version 0, which returns garbage bytes rather than
        // an empty string. A plaintext row left by 1.0.1 would therefore pass an
        // is-it-empty fallback and be signed with that garbage — the snippet still renders,
        // so nothing looks wrong, while every purchase fails signature verification
        // forever. Checked against a real 2.4.7-p10 store, where exactly that happened.
        if (!preg_match('/^\d+:\d+:/', $stored)) {
            return $stored;
        }

        $decrypted = $this->_encryptor->decrypt($stored);

        return $decrypted !== '' && $decrypted !== null ? $decrypted : $stored;
    }
}
