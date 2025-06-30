<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository
 */
class AuthenticationProviderRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(AuthenticationProviderRepository::class));
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertTrue(class_exists(AuthenticationProvider::class));
        $this->assertTrue(class_exists(AuthenticationProviderRepository::class));
    }
}