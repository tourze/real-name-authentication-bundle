<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormStatusController;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormStatusController
 */
class PersonalAuthFormStatusControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormStatusController::class));
    }

    public function testControllerConfiguration(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $controller = new PersonalAuthFormStatusController($personalAuthService);
        
        $this->assertInstanceOf(PersonalAuthFormStatusController::class, $controller);
    }
}