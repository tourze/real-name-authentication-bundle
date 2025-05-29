<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Repository\RealNameAuthenticationRepository;

/**
 * 实名认证记录
 *
 * 存储用户提交的实名认证信息和认证结果
 * 敏感数据采用加密存储
 */
#[ORM\Entity(repositoryClass: RealNameAuthenticationRepository::class)]
#[ORM\Table(
    name: 'real_name_authentication',
    options: ['comment' => '实名认证记录表']
)]
#[ORM\Index(name: 'real_name_authentication_idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'real_name_authentication_idx_status', columns: ['status'])]
#[ORM\Index(name: 'real_name_authentication_idx_type', columns: ['type'])]
#[ORM\Index(name: 'real_name_authentication_idx_create_time', columns: ['create_time'])]
#[UniqueEntity(fields: ['user', 'type'], message: '用户已有该类型的认证记录')]
class RealNameAuthentication implements Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    private string $id;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private UserInterface $user;

    #[ORM\Column(type: Types::STRING, enumType: AuthenticationType::class, options: ['comment' => '认证类型'])]
    private AuthenticationType $type;

    #[ORM\Column(type: Types::STRING, enumType: AuthenticationStatus::class, options: ['comment' => '认证状态'])]
    private AuthenticationStatus $status;

    #[ORM\Column(type: Types::STRING, enumType: AuthenticationMethod::class, options: ['comment' => '认证方式'])]
    private AuthenticationMethod $method;

    #[TrackColumn]
    #[ORM\Column(type: Types::JSON, options: ['comment' => '提交的认证数据（加密存储）'])]
    private array $submittedData = [];

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '验证结果'])]
    private ?array $verificationResult = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '第三方服务商响应数据'])]
    private ?array $providerResponse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '拒绝原因'])]
    private ?string $reason = null;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '创建时间'])]
    private DateTimeImmutable $createTime;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '更新时间'])]
    private DateTimeImmutable $updateTime;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '认证过期时间'])]
    private ?DateTimeImmutable $expireTime = null;

    #[CreatedByColumn]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[CreateIpColumn]
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '创建IP'])]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '更新IP'])]
    private ?string $updatedFromIp = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    private bool $valid = true;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->status = AuthenticationStatus::PENDING;
        $this->createTime = new DateTimeImmutable();
        $this->updateTime = new DateTimeImmutable();
    }

    public function __toString(): string
    {
        $userIdentifier = $this->user?->getUserIdentifier() ?? 'Unknown';
        return sprintf('%s-%s(%s)', $this->type->getLabel(), $this->method->getLabel(), $this->status->getLabel());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getType(): AuthenticationType
    {
        return $this->type;
    }

    public function setType(AuthenticationType $type): void
    {
        $this->type = $type;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getStatus(): AuthenticationStatus
    {
        return $this->status;
    }

    public function setStatus(AuthenticationStatus $status): void
    {
        $this->status = $status;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getMethod(): AuthenticationMethod
    {
        return $this->method;
    }

    public function setMethod(AuthenticationMethod $method): void
    {
        $this->method = $method;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getSubmittedData(): array
    {
        return $this->submittedData;
    }

    public function setSubmittedData(array $submittedData): void
    {
        $this->submittedData = $submittedData;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getVerificationResult(): ?array
    {
        return $this->verificationResult;
    }

    public function setVerificationResult(?array $verificationResult): void
    {
        $this->verificationResult = $verificationResult;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getProviderResponse(): ?array
    {
        return $this->providerResponse;
    }

    public function setProviderResponse(?array $providerResponse): void
    {
        $this->providerResponse = $providerResponse;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
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

    public function getExpireTime(): ?DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(?DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function getUpdatedFromIp(): ?string
    {
        return $this->updatedFromIp;
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

    /**
     * 更新认证状态
     */
    public function updateStatus(
        AuthenticationStatus $status,
        ?array $verificationResult = null,
        ?array $providerResponse = null,
        ?string $reason = null
    ): void {
        $this->status = $status;
        $this->verificationResult = $verificationResult;
        $this->providerResponse = $providerResponse;
        $this->reason = $reason;
        $this->updateTime = new DateTimeImmutable();
    }

    /**
     * 判断认证是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expireTime !== null && $this->expireTime < new DateTimeImmutable();
    }

    /**
     * 判断认证是否成功
     */
    public function isApproved(): bool
    {
        return $this->status === AuthenticationStatus::APPROVED && !$this->isExpired();
    }

    public function setCreatedBy(?string $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function setCreatedFromIp(?string $createdFromIp): void
    {
        $this->createdFromIp = $createdFromIp;
    }

    public function setUpdatedFromIp(?string $updatedFromIp): void
    {
        $this->updatedFromIp = $updatedFromIp;
    }

    /**
     * 获取用户标识符，用于向后兼容
     */
    public function getUserIdentifier(): string
    {
        return $this->user->getUserIdentifier();
    }
}
