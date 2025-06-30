<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormLivenessController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormLivenessController
 */
class PersonalAuthFormLivenessControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormLivenessController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new PersonalAuthFormLivenessController();
        
        $this->assertInstanceOf(PersonalAuthFormLivenessController::class, $controller);
    }
}