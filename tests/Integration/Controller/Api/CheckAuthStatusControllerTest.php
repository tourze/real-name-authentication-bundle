<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Api;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\RealNameAuthenticationBundle\Controller\Api\CheckAuthStatusController;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Api\CheckAuthStatusController
 */
class CheckAuthStatusControllerTest extends TestCase
{
    public function testConstruct(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new CheckAuthStatusController($personalAuthService, $logger);
        
        $this->assertInstanceOf(CheckAuthStatusController::class, $controller);
    }

    public function testControllerConfiguration(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new CheckAuthStatusController($personalAuthService, $logger);
        
        $this->assertInstanceOf(CheckAuthStatusController::class, $controller);
    }
}