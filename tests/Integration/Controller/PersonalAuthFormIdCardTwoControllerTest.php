<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIdCardTwoController;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIdCardTwoController
 */
class PersonalAuthFormIdCardTwoControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(PersonalAuthFormIdCardTwoController::class));
    }

    public function testControllerConfiguration(): void
    {
        $controller = new PersonalAuthFormIdCardTwoController();
        
        $this->assertInstanceOf(PersonalAuthFormIdCardTwoController::class, $controller);
    }
}