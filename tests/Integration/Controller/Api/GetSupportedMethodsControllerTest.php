<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Api;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Api\GetSupportedMethodsController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Api\GetSupportedMethodsController
 */
class GetSupportedMethodsControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(GetSupportedMethodsController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new GetSupportedMethodsController();
        
        $this->assertInstanceOf(GetSupportedMethodsController::class, $controller);
    }
}