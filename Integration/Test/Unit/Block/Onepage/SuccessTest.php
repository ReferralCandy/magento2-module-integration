<?php
namespace ReferralCandy\Integration\Test\Unit\Block\Onepage;

use PHPUnit\Framework\TestCase;
use ReferralCandy\Integration\Block\Onepage\Success;

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
