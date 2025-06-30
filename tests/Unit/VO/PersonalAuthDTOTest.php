<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Unit\VO;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO
 */
class PersonalAuthDTOTest extends TestCase
{
    public function testConstructor(): void
    {
        $user = $this->createMock(UserInterface::class);
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;
        $name = 'test';
        $idCard = '123456789012345678';

        $dto = new PersonalAuthDTO($user, $method, $name, $idCard);

        $this->assertSame($user, $dto->user);
        $this->assertSame($method, $dto->method);
        $this->assertSame($name, $dto->name);
        $this->assertSame($idCard, $dto->idCard);
    }

    public function testToArray(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('test@example.com');
        
        $method = AuthenticationMethod::ID_CARD_TWO_ELEMENTS;
        $name = 'test';
        $idCard = '123456789012345678';

        $dto = new PersonalAuthDTO($user, $method, $name, $idCard);
        $array = $dto->toArray();

        $this->assertArrayHasKey('method', $array);
        $this->assertArrayHasKey('user_identifier', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('id_card', $array);
        $this->assertSame($method->value, $array['method']);
        $this->assertSame('test@example.com', $array['user_identifier']);
        $this->assertSame($name, $array['name']);
        $this->assertSame($idCard, $array['id_card']);
    }
}