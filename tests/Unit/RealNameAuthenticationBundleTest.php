<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\RealNameAuthenticationBundle;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\RealNameAuthenticationBundle
 */
class RealNameAuthenticationBundleTest extends TestCase
{
    public function testBundleExists(): void
    {
        $this->assertTrue(class_exists(RealNameAuthenticationBundle::class));
    }

    public function testBundleConfiguration(): void
    {
        $bundle = new RealNameAuthenticationBundle();
        
        $this->assertInstanceOf(RealNameAuthenticationBundle::class, $bundle);
    }
}