<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationResultRepository;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Repository\AuthenticationResultRepository
 */
class AuthenticationResultRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(AuthenticationResultRepository::class));
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertTrue(class_exists(AuthenticationResult::class));
        $this->assertTrue(class_exists(AuthenticationResultRepository::class));
    }
}