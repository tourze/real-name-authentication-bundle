<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationValidationService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Service\AuthenticationValidationService
 */
class AuthenticationValidationServiceTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(AuthenticationValidationService::class));
    }

    public function testServiceConfiguration(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $service = new AuthenticationValidationService($cache);
        
        $this->assertInstanceOf(AuthenticationValidationService::class, $service);
    }
}