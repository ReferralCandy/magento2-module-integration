<?php
/**
 * Covers the one branch in the module that fails silently and permanently when it is
 * wrong: deciding whether a stored api_secret_key is Magento ciphertext or a plaintext
 * row left behind by 1.0.1.
 *
 * Encryptor::decrypt does NOT reject a value it cannot recognise. A colon-free string is
 * treated as the oldest supported format and Blowfish-decrypted under key version 0,
 * which returns garbage bytes rather than an empty string. Every purchase would then be
 * signed with that garbage: the snippet still renders, so the storefront looks healthy,
 * while ReferralCandy rejects every purchase. Nothing on either side reports it. The
 * envelope check is what prevents that, so it is what this test pins down.
 */

namespace ReferralCandy\Integration\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReferralCandy\Integration\Helper\Configuration;

class ConfigurationTest extends TestCase
{
    const SECRET_PATH = 'referralcandy/rc_general/api_secret_key';

    /**
     * @var EncryptorInterface|MockObject
     */
    private $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = $this->createMock(EncryptorInterface::class);
    }

    public function testDecryptsAValueCarryingMagentosEnvelope()
    {
        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with('0:3:HH+3rDjOGm2wTaGRi0eOWl8XsgQ=')
            ->willReturn('the-real-secret');

        $helper = $this->helperReading('0:3:HH+3rDjOGm2wTaGRi0eOWl8XsgQ=');

        $this->assertSame('the-real-secret', $helper->getApiSecretKey());
    }

    /**
     * The 1.0.1 upgrade path. Handing any of these to decrypt() is the silent-failure bug.
     *
     * @dataProvider valuesThatMustNotBeDecrypted
     */
    public function testReturnsAValueWithoutTheEnvelopeUntouched($stored)
    {
        $this->encryptor->expects($this->never())->method('decrypt');

        $helper = $this->helperReading($stored);

        $this->assertSame($stored, $helper->getApiSecretKey());
    }

    public function valuesThatMustNotBeDecrypted()
    {
        return [
            'plaintext secret stored by 1.0.1' => ['aBcD1234eFgH5678'],
            // A secret may legitimately contain colons; only the leading
            // <keyVersion>:<cipherVersion>: shape means ciphertext.
            'plaintext containing colons'      => ['not:an:envelope'],
            'one numeric segment only'         => ['0:abcdef'],
            'empty'                            => [''],
            'unset'                            => [null],
        ];
    }

    /**
     * Genuine ciphertext whose key is gone from env.php decrypts to an empty string.
     * Returning that would blank the secret; returning the stored value at least keeps a
     * re-save able to fix it.
     */
    public function testFallsBackToTheStoredValueWhenDecryptionYieldsNothing()
    {
        $this->encryptor->method('decrypt')->willReturn('');

        $helper = $this->helperReading('0:3:HH+3rDjOGm2wTaGRi0eOWl8XsgQ=');

        $this->assertSame('0:3:HH+3rDjOGm2wTaGRi0eOWl8XsgQ=', $helper->getApiSecretKey());
    }

    public function testReadsTheSecretAtStoreScopeForTheGivenStore()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::SECRET_PATH, ScopeInterface::SCOPE_STORE, 7)
            ->willReturn('plaintext');

        $this->assertSame('plaintext', $this->helperWith($scopeConfig)->getApiSecretKey(7));
    }

    private function helperReading($stored)
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with(self::SECRET_PATH, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($stored);

        return $this->helperWith($scopeConfig);
    }

    private function helperWith(ScopeConfigInterface $scopeConfig)
    {
        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Configuration($context, $this->encryptor);
    }
}
