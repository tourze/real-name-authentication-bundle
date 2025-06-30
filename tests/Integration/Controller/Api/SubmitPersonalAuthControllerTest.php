<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Api;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\RealNameAuthenticationBundle\Controller\Api\SubmitPersonalAuthController;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Api\SubmitPersonalAuthController
 */
class SubmitPersonalAuthControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(SubmitPersonalAuthController::class));
    }

    public function testControllerConfiguration(): void
    {
        $personalAuthService = $this->createMock(PersonalAuthenticationService::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $controller = new SubmitPersonalAuthController($personalAuthService, $validator, $logger);
        
        $this->assertInstanceOf(SubmitPersonalAuthController::class, $controller);
    }
}