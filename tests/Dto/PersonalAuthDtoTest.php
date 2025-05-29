<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Dto;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Dto\PersonalAuthDto;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 个人认证DTO测试
 */
class PersonalAuthDtoTest extends TestCase
{
    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test_user');
    }

    /**
     * 测试构造函数参数
     */
    public function test_constructor_parameters(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '110101199003071234'
        );

        $this->assertEquals($this->mockUser, $dto->user);
        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $dto->method);
        $this->assertEquals('张三', $dto->name);
        $this->assertEquals('110101199003071234', $dto->idCard);
        $this->assertNull($dto->mobile);
        $this->assertNull($dto->bankCard);
        $this->assertNull($dto->image);
    }

    /**
     * 测试身份证二要素DTO创建
     */
    public function test_id_card_two_elements_dto(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '李四',
            idCard: '220102199105123456'
        );

        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $dto->method);
        $this->assertEquals('李四', $dto->name);
        $this->assertEquals('220102199105123456', $dto->idCard);
    }

    /**
     * 测试运营商三要素DTO创建
     */
    public function test_carrier_three_elements_dto(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::CARRIER_THREE_ELEMENTS,
            name: '王五',
            idCard: '330103199208154321',
            mobile: '13812345678'
        );

        $this->assertEquals(AuthenticationMethod::CARRIER_THREE_ELEMENTS, $dto->method);
        $this->assertEquals('王五', $dto->name);
        $this->assertEquals('330103199208154321', $dto->idCard);
        $this->assertEquals('13812345678', $dto->mobile);
    }

    /**
     * 测试银行卡三要素DTO创建
     */
    public function test_bank_card_three_elements_dto(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
            name: '赵六',
            idCard: '440104199309076543',
            bankCard: '6226200012345678'
        );

        $this->assertEquals(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS, $dto->method);
        $this->assertEquals('赵六', $dto->name);
        $this->assertEquals('440104199309076543', $dto->idCard);
        $this->assertEquals('6226200012345678', $dto->bankCard);
    }

    /**
     * 测试银行卡四要素DTO创建
     */
    public function test_bank_card_four_elements_dto(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            name: '钱七',
            idCard: '110105199410118765',
            bankCard: '6226200087654321',
            mobile: '13987654321'
        );

        $this->assertEquals(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS, $dto->method);
        $this->assertEquals('钱七', $dto->name);
        $this->assertEquals('110105199410118765', $dto->idCard);
        $this->assertEquals('6226200087654321', $dto->bankCard);
        $this->assertEquals('13987654321', $dto->mobile);
    }

    /**
     * 测试活体检测DTO创建
     */
    public function test_liveness_detection_dto(): void
    {
        /** @var UploadedFile&MockObject $mockFile */
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->expects($this->any())
            ->method('getClientOriginalName')
            ->willReturn('photo.jpg');

        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::LIVENESS_DETECTION,
            image: $mockFile
        );

        $this->assertEquals(AuthenticationMethod::LIVENESS_DETECTION, $dto->method);
        $this->assertEquals($mockFile, $dto->image);
        $this->assertNull($dto->name);
        $this->assertNull($dto->idCard);
    }

    /**
     * 测试toArray方法
     */
    public function test_to_array_method(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            name: '测试用户',
            idCard: '110101199001011234',
            bankCard: '6226200012345678',
            mobile: '13812345678'
        );

        $expected = [
            'user_identifier' => 'test_user',
            'method' => 'bank_card_four_elements',
            'name' => '测试用户',
            'id_card' => '110101199001011234',
            'bank_card' => '6226200012345678',
            'mobile' => '13812345678',
        ];

        $this->assertEquals($expected, $dto->toArray());
    }

    /**
     * 测试toArray方法只包含非空字段
     */
    public function test_to_array_with_null_fields(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '测试用户',
            idCard: '110101199001011234'
        );

        $expected = [
            'user_identifier' => 'test_user',
            'method' => 'id_card_two_elements',
            'name' => '测试用户',
            'id_card' => '110101199001011234',
        ];

        $result = $dto->toArray();
        $this->assertEquals($expected, $result);
        $this->assertArrayNotHasKey('mobile', $result);
        $this->assertArrayNotHasKey('bank_card', $result);
    }

    /**
     * 测试readonly属性
     */
    public function test_readonly_properties(): void
    {
        $dto = new PersonalAuthDto(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '测试用户',
            idCard: '110101199001011234'
        );

        // 确保所有属性都是readonly的，这里只验证能正常访问
        $this->assertInstanceOf(UserInterface::class, $dto->user);
        $this->assertInstanceOf(AuthenticationMethod::class, $dto->method);
        $this->assertIsString($dto->name);
        $this->assertIsString($dto->idCard);
    }
} 