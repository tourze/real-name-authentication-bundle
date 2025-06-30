<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\RealNameAuthenticationCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Admin\RealNameAuthenticationCrudController
 */
class RealNameAuthenticationCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            RealNameAuthentication::class,
            RealNameAuthenticationCrudController::getEntityFqcn()
        );
    }
}