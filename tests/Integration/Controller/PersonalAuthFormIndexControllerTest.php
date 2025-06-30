<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIndexController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIndexController
 */
class PersonalAuthFormIndexControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormIndexController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new PersonalAuthFormIndexController();
        
        $this->assertInstanceOf(PersonalAuthFormIndexController::class, $controller);
    }
}