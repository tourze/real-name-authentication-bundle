<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormHistoryController;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormHistoryController
 */
class PersonalAuthFormHistoryControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormHistoryController::class));
    }

    public function testControllerConfiguration(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $controller = new PersonalAuthFormHistoryController($personalAuthService);
        
        $this->assertInstanceOf(PersonalAuthFormHistoryController::class, $controller);
    }
}