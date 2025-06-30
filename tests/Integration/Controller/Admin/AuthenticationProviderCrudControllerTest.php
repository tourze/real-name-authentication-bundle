<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\AuthenticationProviderCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Admin\AuthenticationProviderCrudController
 */
class AuthenticationProviderCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            AuthenticationProvider::class,
            AuthenticationProviderCrudController::getEntityFqcn()
        );
    }

    public function testControllerConfiguration(): void
    {
        $controller = new AuthenticationProviderCrudController();
        
        $this->assertInstanceOf(AuthenticationProviderCrudController::class, $controller);
    }
}