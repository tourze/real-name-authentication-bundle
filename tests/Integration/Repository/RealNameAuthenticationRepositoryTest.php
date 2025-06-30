<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository
 */
class RealNameAuthenticationRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(RealNameAuthenticationRepository::class));
    }

    public function testRepositoryConfiguration(): void
    {
        $this->assertTrue(class_exists(RealNameAuthentication::class));
        $this->assertTrue(class_exists(RealNameAuthenticationRepository::class));
    }
}