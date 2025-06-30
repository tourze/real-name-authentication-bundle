<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormCarrierThreeController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormCarrierThreeController
 */
class PersonalAuthFormCarrierThreeControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormCarrierThreeController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new PersonalAuthFormCarrierThreeController();
        
        $this->assertInstanceOf(PersonalAuthFormCarrierThreeController::class, $controller);
    }
}