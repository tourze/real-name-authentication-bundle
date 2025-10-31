<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository;

/**
 * 认证提供商服务
 *
 * 管理认证提供商的选择和调用
 */
#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'real_name_authentication')]
class AuthenticationProviderService
{
    public function __construct(
        private readonly AuthenticationProviderRepository $providerRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 获取支持指定认证方式的可用提供商
     *
     * @return array<AuthenticationProvider>
     */
    public function getAvailableProviders(AuthenticationMethod $method): array
    {
        return $this->providerRepository->findByMethod($method);
    }

    /**
     * 选择最佳提供商
     *
     * @param array<string, mixed> $criteria
     */
    public function selectBestProvider(AuthenticationMethod $method, array $criteria = []): ?AuthenticationProvider
    {
        return $this->providerRepository->findBestProviderForMethod($method);
    }

    /**
     * 执行认证验证
     *
     * @param array<string, mixed> $data
     */
    public function executeVerification(AuthenticationProvider $provider, array $data): AuthenticationResult
    {
        $startTime = microtime(true);
        $requestId = uniqid('auth_', true);

        try {
            try {
                $this->logger->info('开始认证验证', [
                    'provider' => $provider->getCode(),
                    'request_id' => $requestId,
                ]);
            } catch (\Throwable $logError) {
                // 忽略日志记录错误
            }

            // 构建请求数据
            $requestData = $this->buildRequestData($provider, $data);

            // 记录请求审计日志
            try {
                $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s.u');
                $this->logger->info('发起认证API请求', [
                    'provider' => $provider->getCode(),
                    'request_id' => $requestId,
                    'endpoint' => $provider->getApiEndpoint(),
                    'request_size' => strlen((string) json_encode($requestData)),
                    'timestamp' => $timestamp,
                ]);
            } catch (\Throwable $logError) {
                // 忽略日志记录错误
            }

            // 发送HTTP请求
            // @audit-logged 外部系统交互：HttpClientInterface::request() - 已记录审计日志（请求内容、响应结果、耗时、异常等）
            $response = $this->httpClient->request('POST', $provider->getApiEndpoint(), [
                'json' => $requestData,
                'headers' => $this->buildHeaders($provider),
                'timeout' => 30,
            ]);

            // 记录响应审计日志
            try {
                $this->logger->info('收到认证API响应', [
                    'provider' => $provider->getCode(),
                    'request_id' => $requestId,
                    'status_code' => $response->getStatusCode(),
                    'response_size' => strlen($response->getContent()),
                    'processing_time_ms' => intval((microtime(true) - $startTime) * 1000),
                ]);
            } catch (\Throwable $logError) {
                // 忽略日志记录错误
            }

            /** @var array<string, mixed> $responseData */
            $responseData = $response->toArray();
            $processingTime = intval((microtime(true) - $startTime) * 1000);

            // 解析响应
            $result = $this->handleProviderResponse($responseData, $provider);

            // 创建认证结果
            $authResult = new AuthenticationResult();
            $authResult->setAuthentication($this->createDummyAuthentication()); // 临时解决方案
            $authResult->setProvider($provider);
            $authResult->setRequestId($requestId);
            $authResult->setSuccess($result['success']);
            $authResult->setResponseData($responseData);
            $authResult->setProcessingTime($processingTime);
            $authResult->setConfidence($result['confidence'] ?? null);
            $authResult->setErrorCode($result['error_code'] ?? null);
            $authResult->setErrorMessage($result['error_message'] ?? null);

            // 记录提供商使用情况
            try {
                $this->logProviderUsage($provider, $result['success']);
            } catch (\Throwable $logError) {
                // 忽略日志记录错误
            }

            return $authResult;
        } catch (\Throwable $e) {
            $processingTime = intval((microtime(true) - $startTime) * 1000);

            try {
                $this->logger->error('认证验证失败', [
                    'provider' => $provider->getCode(),
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable $logError) {
                // 忽略日志记录错误，避免影响主业务逻辑
            }

            // 记录失败的使用情况
            try {
                $this->logProviderUsage($provider, false);
            } catch (\Throwable $logError) {
                // 忽略日志记录错误，避免影响主业务逻辑
            }

            $failureResult = new AuthenticationResult();
            $failureResult->setAuthentication($this->createDummyAuthentication()); // 临时解决方案
            $failureResult->setProvider($provider);
            $failureResult->setRequestId($requestId);
            $failureResult->setSuccess(false);
            $failureResult->setResponseData([]);
            $failureResult->setProcessingTime($processingTime);
            $failureResult->setConfidence(null);
            $failureResult->setErrorCode('PROVIDER_ERROR');
            $failureResult->setErrorMessage($e->getMessage());

            return $failureResult;
        }
    }

    /**
     * 处理提供商响应
     *
     * @param array<string, mixed> $response
     * @return array{success: bool, confidence: float|null, error_code: string|null, error_message: string|null}
     */
    public function handleProviderResponse(array $response, AuthenticationProvider $provider): array
    {
        $success = $this->parseSuccessStatus($response);
        $confidence = $this->parseConfidence($response);

        if ($success) {
            return [
                'success' => true,
                'confidence' => $confidence,
                'error_code' => null,
                'error_message' => null,
            ];
        }

        return [
            'success' => false,
            'confidence' => $confidence,
            'error_code' => $this->parseErrorCode($response),
            'error_message' => $this->parseErrorMessage($response),
        ];
    }

    /**
     * 解析成功状态
     *
     * @param array<string, mixed> $response
     */
    private function parseSuccessStatus(array $response): bool
    {
        if (isset($response['code']) && '200' === $response['code']) {
            return true;
        }

        return isset($response['success']) && true === $response['success'];
    }

    /**
     * 解析置信度
     *
     * @param array<string, mixed> $response
     */
    private function parseConfidence(array $response): ?float
    {
        if (isset($response['confidence'])) {
            $confidence = $response['confidence'];

            return is_numeric($confidence) ? (float) $confidence : null;
        }

        if (isset($response['score'])) {
            $score = $response['score'];

            return is_numeric($score) ? (float) $score : null;
        }

        return null;
    }

    /**
     * 解析错误代码
     *
     * @param array<string, mixed> $response
     */
    private function parseErrorCode(array $response): string
    {
        $errorCode = $response['error_code'] ?? ($response['code'] ?? 'UNKNOWN_ERROR');

        return is_string($errorCode) ? $errorCode : 'UNKNOWN_ERROR';
    }

    /**
     * 解析错误消息
     *
     * @param array<string, mixed> $response
     */
    private function parseErrorMessage(array $response): string
    {
        $errorMessage = $response['error_message'] ?? ($response['message'] ?? '认证失败');

        return is_string($errorMessage) ? $errorMessage : '认证失败';
    }

    /**
     * 记录提供商使用情况
     */
    public function logProviderUsage(AuthenticationProvider $provider, bool $success): void
    {
        try {
            $this->logger->info('提供商使用记录', [
                'provider' => $provider->getCode(),
                'success' => $success,
                'timestamp' => time(),
            ]);
        } catch (\Throwable $logError) {
            // 忽略日志记录错误
        }

        // 这里可以扩展更详细的统计逻辑
        // 例如：成功率、响应时间统计等
    }

    /**
     * 构建请求数据
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildRequestData(AuthenticationProvider $provider, array $data): array
    {
        $requestData = [];

        // 添加认证信息
        $appId = $provider->getConfigValue('app_id');
        $appSecret = $provider->getConfigValue('app_secret');

        if (null !== $appId && '' !== $appId) {
            $requestData['app_id'] = $appId;
        }

        // 添加签名
        if (is_string($appSecret) && '' !== $appSecret) {
            $requestData['timestamp'] = time();
            $requestData['nonce'] = uniqid();
            $requestData['sign'] = $this->generateSignature($data, $appSecret, $requestData['timestamp'], $requestData['nonce']);
        }

        // 添加业务数据
        return array_merge($requestData, $data);
    }

    /**
     * 构建请求头
     *
     * @return array<string, string>
     */
    private function buildHeaders(AuthenticationProvider $provider): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'RealNameAuth/1.0',
        ];

        // 添加认证头
        $apiKey = $provider->getConfigValue('api_key');
        if (is_string($apiKey) && '' !== $apiKey) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        return $headers;
    }

    /**
     * 生成签名
     *
     * @param array<string, mixed> $data
     */
    private function generateSignature(array $data, string $secret, int $timestamp, string $nonce): string
    {
        // 简单的签名算法，实际使用时需要根据提供商要求调整
        $params = array_merge($data, ['timestamp' => $timestamp, 'nonce' => $nonce]);
        ksort($params);

        $string = '';
        foreach ($params as $key => $value) {
            $valueStr = is_scalar($value) ? (string) $value : '';
            $string .= $key . '=' . $valueStr . '&';
        }
        $string .= 'secret=' . $secret;

        return md5($string);
    }

    /**
     * 创建临时认证对象（临时解决方案）
     */
    private function createDummyAuthentication(): RealNameAuthentication
    {
        // 创建一个虚拟用户用于临时认证记录
        $dummyUser = new class implements UserInterface {
            public function getUserIdentifier(): string
            {
                return 'dummy_verification_user';
            }

            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }
        };

        // 创建临时认证记录
        $authentication = new RealNameAuthentication();
        $authentication->setUser($dummyUser);
        $authentication->setType(AuthenticationType::PERSONAL);
        $authentication->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        $authentication->setSubmittedData([]); // 设置空的提交数据

        return $authentication;
    }
}
