<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository;

/**
 * 认证提供商服务
 *
 * 管理认证提供商的选择和调用
 */
class AuthenticationProviderService
{
    public function __construct(
        private readonly AuthenticationProviderRepository $providerRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 获取支持指定认证方式的可用提供商
     */
    public function getAvailableProviders(AuthenticationMethod $method): array
    {
        return $this->providerRepository->findByMethod($method);
    }

    /**
     * 选择最佳提供商
     */
    public function selectBestProvider(AuthenticationMethod $method, array $criteria = []): ?AuthenticationProvider
    {
        return $this->providerRepository->findBestProviderForMethod($method);
    }

    /**
     * 执行认证验证
     */
    public function executeVerification(AuthenticationProvider $provider, array $data): AuthenticationResult
    {
        $startTime = microtime(true);
        $requestId = uniqid('auth_', true);

        try {
            $this->logger->info('开始认证验证', [
                'provider' => $provider->getCode(),
                'request_id' => $requestId,
            ]);

            // 构建请求数据
            $requestData = $this->buildRequestData($provider, $data);

            // 发送HTTP请求
            $response = $this->httpClient->request('POST', $provider->getApiEndpoint(), [
                'json' => $requestData,
                'headers' => $this->buildHeaders($provider),
                'timeout' => 30,
            ]);

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
            $this->logProviderUsage($provider, $result['success']);

            return $authResult;

        } catch (\Throwable $e) {
            $processingTime = intval((microtime(true) - $startTime) * 1000);

            $this->logger->error('认证验证失败', [
                'provider' => $provider->getCode(),
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            // 记录失败的使用情况
            $this->logProviderUsage($provider, false);

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
     */
    public function handleProviderResponse(array $response, AuthenticationProvider $provider): array
    {
        // 根据不同提供商的响应格式进行解析
        // 这里使用通用格式，实际使用时需要根据具体提供商调整

        $result = [
            'success' => false,
            'confidence' => null,
            'error_code' => null,
            'error_message' => null,
        ];

        // 解析成功状态
        if (isset($response['code']) && $response['code'] === '200') {
            $result['success'] = true;
        } elseif (isset($response['success']) && $response['success'] === true) {
            $result['success'] = true;
        }

        // 解析置信度
        if (isset($response['confidence'])) {
            $result['confidence'] = floatval($response['confidence']);
        } elseif (isset($response['score'])) {
            $result['confidence'] = floatval($response['score']);
        }

        // 解析错误信息
        if (!$result['success']) {
            $result['error_code'] = $response['error_code'] ?? $response['code'] ?? 'UNKNOWN_ERROR';
            $result['error_message'] = $response['error_message'] ?? $response['message'] ?? '认证失败';
        }

        return $result;
    }

    /**
     * 记录提供商使用情况
     */
    public function logProviderUsage(AuthenticationProvider $provider, bool $success): void
    {
        $this->logger->info('提供商使用记录', [
            'provider' => $provider->getCode(),
            'success' => $success,
            'timestamp' => time(),
        ]);

        // 这里可以扩展更详细的统计逻辑
        // 例如：成功率、响应时间统计等
    }

    /**
     * 构建请求数据
     */
    private function buildRequestData(AuthenticationProvider $provider, array $data): array
    {
        $requestData = [];

        // 添加认证信息
        $appId = $provider->getConfigValue('app_id');
        $appSecret = $provider->getConfigValue('app_secret');

        if ($appId !== null && $appId !== '') {
            $requestData['app_id'] = $appId;
        }

        // 添加签名
        if ($appSecret !== null && $appSecret !== '') {
            $requestData['timestamp'] = time();
            $requestData['nonce'] = uniqid();
            $requestData['sign'] = $this->generateSignature($data, $appSecret, $requestData['timestamp'], $requestData['nonce']);
        }

        // 添加业务数据
        $requestData = array_merge($requestData, $data);

        return $requestData;
    }

    /**
     * 构建请求头
     */
    private function buildHeaders(AuthenticationProvider $provider): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'RealNameAuth/1.0',
        ];

        // 添加认证头
        $apiKey = $provider->getConfigValue('api_key');
        if ($apiKey !== null && $apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        return $headers;
    }

    /**
     * 生成签名
     */
    private function generateSignature(array $data, string $secret, int $timestamp, string $nonce): string
    {
        // 简单的签名算法，实际使用时需要根据提供商要求调整
        $params = array_merge($data, ['timestamp' => $timestamp, 'nonce' => $nonce]);
        ksort($params);

        $string = '';
        foreach ($params as $key => $value) {
            $string .= $key . '=' . $value . '&';
        }
        $string .= 'secret=' . $secret;

        return md5($string);
    }

    /**
     * 创建临时认证对象（临时解决方案）
     */
    private function createDummyAuthentication(): RealNameAuthentication
    {
        // 这是一个临时解决方案，实际使用时应该传入真正的认证对象
        // 或者重构 AuthenticationResult 的构造函数
        // 注意：此方法应该被重构，因为创建 RealNameAuthentication 需要真实的用户对象
        throw new AuthenticationException('createTempAuthentication 方法需要重构，不能创建没有用户的认证记录');
    }
}
