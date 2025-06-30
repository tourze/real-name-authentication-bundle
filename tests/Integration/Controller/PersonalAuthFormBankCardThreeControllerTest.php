<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardThreeController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardThreeController
 */
class PersonalAuthFormBankCardThreeControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormBankCardThreeController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new PersonalAuthFormBankCardThreeController();
        
        $this->assertInstanceOf(PersonalAuthFormBankCardThreeController::class, $controller);
    }
}