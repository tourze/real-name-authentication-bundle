<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationResultRepository;

/**
 * 认证结果
 *
 * 存储每次认证请求的详细结果信息
 */
#[ORM\Entity(repositoryClass: AuthenticationResultRepository::class)]
#[ORM\Table(
    name: 'authentication_result',
    options: ['comment' => '认证结果表']
)]
#[ORM\Index(name: 'authentication_result_idx_authentication_id', columns: ['authentication_id'])]
#[ORM\Index(name: 'authentication_result_idx_provider_id', columns: ['provider_id'])]
#[ORM\Index(name: 'authentication_result_idx_request_id', columns: ['request_id'])]
#[ORM\Index(name: 'authentication_result_idx_success', columns: ['success'])]
class AuthenticationResult implements Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    private string $id;

    #[ORM\ManyToOne(targetEntity: RealNameAuthentication::class)]
    #[ORM\JoinColumn(name: 'authentication_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RealNameAuthentication $authentication;

    #[ORM\ManyToOne(targetEntity: AuthenticationProvider::class, inversedBy: 'results')]
    #[ORM\JoinColumn(name: 'provider_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AuthenticationProvider $provider;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '请求ID'])]
    private string $requestId;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否成功'])]
    private bool $success;

    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => '置信度（0-1之间）'])]
    private ?float $confidence = null;

    #[ORM\Column(type: Types::JSON, options: ['comment' => '响应数据'])]
    private array $responseData = [];

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '错误代码'])]
    private ?string $errorCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '错误消息'])]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '处理时间（毫秒）'])]
    private int $processingTime;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '创建时间'])]
    private DateTimeImmutable $createTime;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '更新时间'])]
    private DateTimeImmutable $updateTime;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    private bool $valid = true;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createTime = new DateTimeImmutable();
        $this->updateTime = new DateTimeImmutable();
    }

    public function __toString(): string
    {
        $status = $this->success ? '成功' : '失败';
        return sprintf('%s - %s (%s)', $this->provider->getName(), $status, $this->requestId);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAuthentication(): RealNameAuthentication
    {
        return $this->authentication;
    }

    public function setAuthentication(RealNameAuthentication $authentication): void
    {
        $this->authentication = $authentication;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getProvider(): AuthenticationProvider
    {
        return $this->provider;
    }

    public function setProvider(AuthenticationProvider $provider): void
    {
        $this->provider = $provider;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
        $this->updateTime = new DateTimeImmutable();
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): void
    {
        $this->success = $success;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    public function setConfidence(?float $confidence): void
    {
        $this->confidence = $confidence;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function setResponseData(array $responseData): void
    {
        $this->responseData = $responseData;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function setErrorCode(?string $errorCode): void
    {
        $this->errorCode = $errorCode;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getProcessingTime(): int
    {
        return $this->processingTime;
    }

    public function setProcessingTime(int $processingTime): void
    {
        $this->processingTime = $processingTime;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getCreateTime(): DateTimeImmutable
    {
        return $this->createTime;
    }

    public function getUpdateTime(): DateTimeImmutable
    {
        return $this->updateTime;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
        $this->updateTime = new DateTimeImmutable();
    }

    public function setCreateTime(DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function setUpdateTime(DateTimeImmutable $updateTime): void
    {
        $this->updateTime = $updateTime;
    }
}
