<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Service;

use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 认证方式检测器
 *
 * 负责根据数据字段自动检测认证方式
 */
final class AuthenticationMethodDetector
{
    /**
     * 确定认证方式
     *
     * @param array<string, mixed> $data
     */
    public function detect(array $data): ?AuthenticationMethod
    {
        // 如果有明确指定认证方式,优先使用
        $explicitMethod = $this->getExplicitMethod($data);
        if (null !== $explicitMethod) {
            return $explicitMethod;
        }

        // 根据数据字段自动判断
        return $this->detectByFields($data);
    }

    /**
     * 获取明确指定的认证方式
     *
     * @param array<string, mixed> $data
     */
    private function getExplicitMethod(array $data): ?AuthenticationMethod
    {
        if (!isset($data['method']) || !is_string($data['method'])) {
            return null;
        }

        try {
            return AuthenticationMethod::from($data['method']);
        } catch (\ValueError) {
            // 忽略无效的认证方式,继续自动检测
            return null;
        }
    }

    /**
     * 根据字段内容检测认证方式
     *
     * @param array<string, mixed> $data
     */
    private function detectByFields(array $data): ?AuthenticationMethod
    {
        $fieldStatus = $this->analyzeFields($data);

        return $this->mapToMethod($fieldStatus);
    }

    /**
     * 分析数据字段完整性
     *
     * @param array<string, mixed> $data
     * @return array<string, bool>
     */
    private function analyzeFields(array $data): array
    {
        return [
            'hasName' => '' !== ($data['name'] ?? ''),
            'hasIdCard' => '' !== ($data['id_card'] ?? ''),
            'hasMobile' => '' !== ($data['mobile'] ?? ''),
            'hasBankCard' => '' !== ($data['bank_card'] ?? ''),
        ];
    }

    /**
     * 将字段状态映射到认证方式
     *
     * @param array<string, bool> $fieldStatus
     */
    private function mapToMethod(array $fieldStatus): ?AuthenticationMethod
    {
        // 根据字段组合确定认证方式(按复杂度降序)
        $authMaps = $this->getAuthenticationMaps();

        foreach ($authMaps as $config) {
            if ($this->hasRequiredFields($fieldStatus, $config['fields'])) {
                return $config['method'];
            }
        }

        return null;
    }

    /**
     * 获取认证方式映射配置
     *
     * @return array<string, array{fields: array<int, string>, method: AuthenticationMethod}>
     */
    private function getAuthenticationMaps(): array
    {
        return [
            'four_elements' => [
                'fields' => ['name', 'id_card', 'mobile', 'bank_card'],
                'method' => AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS,
            ],
            'bank_three' => [
                'fields' => ['name', 'id_card', 'bank_card'],
                'method' => AuthenticationMethod::BANK_CARD_THREE_ELEMENTS,
            ],
            'carrier_three' => [
                'fields' => ['name', 'id_card', 'mobile'],
                'method' => AuthenticationMethod::CARRIER_THREE_ELEMENTS,
            ],
            'id_two' => [
                'fields' => ['name', 'id_card'],
                'method' => AuthenticationMethod::ID_CARD_TWO_ELEMENTS,
            ],
        ];
    }

    /**
     * 检查是否具备所有必需字段
     *
     * @param array<string, bool> $fieldStatus
     * @param array<int, string> $requiredFields
     */
    private function hasRequiredFields(array $fieldStatus, array $requiredFields): bool
    {
        $fieldMapping = [
            'name' => 'hasName',
            'id_card' => 'hasIdCard',
            'mobile' => 'hasMobile',
            'bank_card' => 'hasBankCard',
        ];

        foreach ($requiredFields as $field) {
            $statusKey = $fieldMapping[$field] ?? null;
            if (null === $statusKey || !($fieldStatus[$statusKey] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
