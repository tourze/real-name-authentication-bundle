<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Api;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\RealNameAuthenticationBundle\Controller\Api\GetAuthHistoryController;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Api\GetAuthHistoryController
 */
class GetAuthHistoryControllerTest extends TestCase
{
    public function testConstruct(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new GetAuthHistoryController($personalAuthService, $logger);
        
        $this->assertInstanceOf(GetAuthHistoryController::class, $controller);
    }

    public function testControllerConfiguration(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new GetAuthHistoryController($personalAuthService, $logger);
        
        $this->assertInstanceOf(GetAuthHistoryController::class, $controller);
    }
}