<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\RealNameAuthenticationBundle\Service\AdminMenu;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Service\AdminMenu
 */
class AdminMenuTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(AdminMenu::class));
    }

    public function testServiceConfiguration(): void
    {
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $service = new AdminMenu($linkGenerator);
        
        $this->assertInstanceOf(AdminMenu::class, $service);
    }
}