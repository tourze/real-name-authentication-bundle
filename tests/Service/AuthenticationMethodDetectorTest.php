<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationMethodDetector;

/**
 * @internal
 */
#[CoversClass(AuthenticationMethodDetector::class)]
#[RunTestsInSeparateProcesses]
final class AuthenticationMethodDetectorTest extends AbstractIntegrationTestCase
{
    private AuthenticationMethodDetector $detector;

    protected function onSetUp(): void
    {
        $this->detector = self::getService(AuthenticationMethodDetector::class);
    }

    public function testDetectWithExplicitMethod(): void
    {
        $data = [
            'method' => 'id_card_two_elements',
            'name' => '张三',
            'id_card' => '110101199001011234',
        ];

        $result = $this->detector->detect($data);

        $this->assertSame(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result);
    }

    public function testDetectWithInvalidExplicitMethod(): void
    {
        $data = [
            'method' => 'invalid_method',
            'name' => '张三',
            'id_card' => '110101199001011234',
        ];

        $result = $this->detector->detect($data);

        // 应该回退到字段检测
        $this->assertSame(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result);
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('authenticationMethodDataProvider')]
    public function testDetectByFields(array $data, ?AuthenticationMethod $expectedMethod): void
    {
        $result = $this->detector->detect($data);

        $this->assertSame($expectedMethod, $result);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: AuthenticationMethod|null}>
     */
    public static function authenticationMethodDataProvider(): array
    {
        return [
            'four_elements' => [
                [
                    'name' => '张三',
                    'id_card' => '110101199001011234',
                    'mobile' => '13800138000',
                    'bank_card' => '6222021234567894',
                ],
                AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            ],
            'bank_three_elements' => [
                [
                    'name' => '李四',
                    'id_card' => '110101199002022345',
                    'bank_card' => '6222021234567895',
                ],
                AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
            ],
            'carrier_three_elements' => [
                [
                    'name' => '王五',
                    'id_card' => '110101199003033456',
                    'mobile' => '13800138001',
                ],
                AuthenticationMethod::CARRIER_THREE_ELEMENTS,
            ],
            'id_two_elements' => [
                [
                    'name' => '赵六',
                    'id_card' => '110101199004044567',
                ],
                AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ],
            'no_method_detected' => [
                [
                    'name' => '钱七',
                ],
                null,
            ],
            'empty_data' => [
                [],
                null,
            ],
        ];
    }

    public function testDetectPrioritizesFourElements(): void
    {
        // 当所有字段都存在时,应该优先选择四要素认证
        $data = [
            'name' => '张三',
            'id_card' => '110101199001011234',
            'mobile' => '13800138000',
            'bank_card' => '6222021234567894',
        ];

        $result = $this->detector->detect($data);

        $this->assertSame(AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS, $result);
    }

    public function testDetectWithEmptyFields(): void
    {
        // 空字符串字段应该被视为不存在
        $data = [
            'name' => '张三',
            'id_card' => '110101199001011234',
            'mobile' => '',
            'bank_card' => '',
        ];

        $result = $this->detector->detect($data);

        // 应该检测为二要素
        $this->assertSame(AuthenticationMethod::ID_CARD_TWO_ELEMENTS, $result);
    }
}
