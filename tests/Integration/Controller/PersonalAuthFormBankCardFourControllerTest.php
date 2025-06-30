<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardFourController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardFourController
 */
class PersonalAuthFormBankCardFourControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormBankCardFourController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new PersonalAuthFormBankCardFourController();
        
        $this->assertInstanceOf(PersonalAuthFormBankCardFourController::class, $controller);
    }
}