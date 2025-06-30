<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\AuthenticationResultCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Admin\AuthenticationResultCrudController
 */
class AuthenticationResultCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            AuthenticationResult::class,
            AuthenticationResultCrudController::getEntityFqcn()
        );
    }

    public function testControllerConfiguration(): void
    {
        $controller = new AuthenticationResultCrudController();
        
        $this->assertInstanceOf(AuthenticationResultCrudController::class, $controller);
    }
}