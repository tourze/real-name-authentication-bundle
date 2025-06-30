<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\DependencyInjection\RealNameAuthenticationExtension;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\DependencyInjection\RealNameAuthenticationExtension
 */
class RealNameAuthenticationExtensionTest extends TestCase
{
    public function testExtensionExists(): void
    {
        $this->assertTrue(class_exists(RealNameAuthenticationExtension::class));
    }

    public function testExtensionConfiguration(): void
    {
        $extension = new RealNameAuthenticationExtension();
        
        $this->assertInstanceOf(RealNameAuthenticationExtension::class, $extension);
    }
}