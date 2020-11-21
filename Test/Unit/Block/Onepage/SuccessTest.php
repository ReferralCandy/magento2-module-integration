<?php
namespace ReferralCandy\MagentoIntegration\Test\Unit\Block\Onepage;

use PHPUnit\Framework\TestCase;
use ReferralCandy\MagentoIntegration\Block\Onepage\Success;

class SuccessTest extends TestCase
{
    /**
     * @var Success
     */
    private $block;

    protected function setUp(): void
    {
        $this->block = new Success();
    }

    public function testSuccessInstance()
    {
        $this->assertInstanceOf(Success::class, $this->block);
    }
}
