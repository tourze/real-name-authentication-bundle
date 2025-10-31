<?php

namespace Tourze\RealNameAuthenticationBundle\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;

/**
 * 个人认证DTO测试
 *
 * @internal
 */
#[CoversClass(PersonalAuthDTO::class)]
final class PersonalAuthDtoTest extends TestCase
{
    private UserInterface&MockObject $mockUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUser = $this->createMock(UserInterface::class);
        $this->mockUser->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test_user')
        ;
    }

    /**
     * 测试构造函数参数
     */
    public function testConstructorParameters(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '张三',
            idCard: '11010119900101100X'
        );

        $this->assertEquals($this->mockUser, $dto->user);
        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $dto->method);
        $this->assertEquals('张三', $dto->name);
        $this->assertEquals('11010119900101100X', $dto->idCard);
        $this->assertNull($dto->mobile);
        $this->assertNull($dto->bankCard);
        $this->assertNull($dto->image);
    }

    /**
     * 测试身份证二要素DTO创建
     */
    public function testIdCardTwoElementsDto(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '李四',
            idCard: '110101199002021007'
        );

        $this->assertEquals(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $dto->method);
        $this->assertEquals('李四', $dto->name);
        $this->assertEquals('110101199002021007', $dto->idCard);
    }

    /**
     * 测试运营商三要素DTO创建
     */
    public function testCarrierThreeElementsDto(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::CARRIER_THREE_ELEMENTS,
            name: '王五',
            idCard: '110101199003031004',
            mobile: '13812345678'
        );

        $this->assertEquals(AuthenticationMethod::CARRIER_THREE_ELEMENTS, $dto->method);
        $this->assertEquals('王五', $dto->name);
        $this->assertEquals('110101199003031004', $dto->idCard);
        $this->assertEquals('13812345678', $dto->mobile);
    }

    /**
     * 测试银行卡三要素DTO创建
     */
    public function testBankCardThreeElementsDto(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
            name: '赵六',
            idCard: '11010119900101100X',
            bankCard: '6222021234567894'
        );

        $this->assertEquals(AuthenticationMethod::BANK_CARD_THREE_ELEMENTS, $dto->method);
        $this->assertEquals('赵六', $dto->name);
        $this->assertEquals('11010119900101100X', $dto->idCard);
        $this->assertEquals('6222021234567894', $dto->bankCard);
    }

    /**
     * 测试银行卡四要素DTO创建
     */
    public function testBankCardFourElementsDto(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            name: '钱七',
            idCard: '110101199002021007',
            bankCard: '6228481234567894',
            mobile: '13987654321'
        );

        $this->assertEquals(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS, $dto->method);
        $this->assertEquals('钱七', $dto->name);
        $this->assertEquals('110101199002021007', $dto->idCard);
        $this->assertEquals('6228481234567894', $dto->bankCard);
        $this->assertEquals('13987654321', $dto->mobile);
    }

    /**
     * 测试活体检测DTO创建
     */
    public function testLivenessDetectionDto(): void
    {
        /*
         * 使用具体类进行 Mock 的原因：
         * 1) UploadedFile 是 Symfony 的具体文件上传类，没有对应的接口抽象
         * 2) 这种使用是合理的，因为我们需要测试 DTO 处理文件上传的行为，需要模拟文件对象
         * 3) 替代方案：可以使用真实的文件对象，但在单元测试中 Mock 文件上传类是标准做法，避免了文件系统操作
         */
        $mockFile = $this->createMock(UploadedFile::class);
        $mockFile->expects($this->any())
            ->method('getClientOriginalName')
            ->willReturn('photo.jpg')
        ;

        $dto = new PersonalAuthDTO(
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
    public function testToArrayMethod(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            name: '测试用户',
            idCard: '11010119900101100X',
            bankCard: '6222021234567894',
            mobile: '13812345678'
        );

        $expected = [
            'user_identifier' => 'test_user',
            'method' => 'bank_card_four_elements',
            'name' => '测试用户',
            'id_card' => '11010119900101100X',
            'bank_card' => '6222021234567894',
            'mobile' => '13812345678',
        ];

        $this->assertEquals($expected, $dto->toArray());
    }

    /**
     * 测试toArray方法只包含非空字段
     */
    public function testToArrayWithNullFields(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '测试用户',
            idCard: '11010119900101100X'
        );

        $expected = [
            'user_identifier' => 'test_user',
            'method' => 'id_card_two_elements',
            'name' => '测试用户',
            'id_card' => '11010119900101100X',
        ];

        $result = $dto->toArray();
        $this->assertEquals($expected, $result);
        $this->assertArrayNotHasKey('mobile', $result);
        $this->assertArrayNotHasKey('bank_card', $result);
    }

    /**
     * 测试readonly属性
     */
    public function testReadonlyProperties(): void
    {
        $dto = new PersonalAuthDTO(
            user: $this->mockUser,
            method: AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            name: '测试用户',
            idCard: '11010119900101100X'
        );

        // 确保所有属性都是readonly的，这里只验证能正常访问
        $this->assertInstanceOf(UserInterface::class, $dto->user);
        $this->assertInstanceOf(AuthenticationMethod::class, $dto->method);
    }
}
