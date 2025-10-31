<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
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
#[UniqueEntity(fields: ['user', 'type'], message: '用户已有该类型的认证记录')]
class RealNameAuthentication implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\CustomIdGenerator]
    #[ORM\Column(name: 'id', type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    #[Assert\Length(max: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull]
    #[Assert\Valid]
    private UserInterface $user;

    #[ORM\Column(name: 'type', type: Types::STRING, enumType: AuthenticationType::class, options: ['comment' => '认证类型'])]
    #[IndexColumn]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AuthenticationType::class, 'cases'])]
    private AuthenticationType $type;

    #[ORM\Column(name: 'status', type: Types::STRING, enumType: AuthenticationStatus::class, options: ['comment' => '认证状态'])]
    #[IndexColumn]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AuthenticationStatus::class, 'cases'])]
    private AuthenticationStatus $status;

    #[ORM\Column(name: 'method', type: Types::STRING, enumType: AuthenticationMethod::class, options: ['comment' => '认证方式'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AuthenticationMethod::class, 'cases'])]
    private AuthenticationMethod $method;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'submitted_data', type: Types::JSON, options: ['comment' => '提交的认证数据（加密存储）'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'array')]
    private array $submittedData = [];

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'verification_result', type: Types::JSON, nullable: true, options: ['comment' => '验证结果'])]
    #[Assert\Type(type: 'array')]
    private ?array $verificationResult = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'provider_response', type: Types::JSON, nullable: true, options: ['comment' => '第三方服务商响应数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $providerResponse = null;

    #[ORM\Column(name: 'reason', type: Types::TEXT, nullable: true, options: ['comment' => '拒绝原因'])]
    #[Assert\Length(max: 65535)]
    private ?string $reason = null;

    #[ORM\Column(name: 'expire_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '认证过期时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $expireTime = null;

    #[ORM\Column(name: 'valid', type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private bool $valid = true;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->status = AuthenticationStatus::PENDING;
    }

    public function __toString(): string
    {
        $userIdentifier = $this->user->getUserIdentifier();

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
    }

    public function getType(): AuthenticationType
    {
        return $this->type;
    }

    public function setType(AuthenticationType $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): AuthenticationStatus
    {
        return $this->status;
    }

    public function setStatus(AuthenticationStatus $status): void
    {
        $this->status = $status;
    }

    public function getMethod(): AuthenticationMethod
    {
        return $this->method;
    }

    public function setMethod(AuthenticationMethod $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubmittedData(): array
    {
        return $this->submittedData;
    }

    /**
     * @param array<string, mixed> $submittedData
     */
    public function setSubmittedData(array $submittedData): void
    {
        $this->submittedData = $submittedData;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVerificationResult(): ?array
    {
        return $this->verificationResult;
    }

    /**
     * @param array<string, mixed>|null $verificationResult
     */
    public function setVerificationResult(?array $verificationResult): void
    {
        $this->verificationResult = $verificationResult;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProviderResponse(): ?array
    {
        return $this->providerResponse;
    }

    /**
     * @param array<string, mixed>|null $providerResponse
     */
    public function setProviderResponse(?array $providerResponse): void
    {
        $this->providerResponse = $providerResponse;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    public function getExpireTime(): ?\DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(?\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    /**
     * 更新认证状态
     *
     * @param array<string, mixed>|null $verificationResult
     * @param array<string, mixed>|null $providerResponse
     */
    public function updateStatus(
        AuthenticationStatus $status,
        ?array $verificationResult = null,
        ?array $providerResponse = null,
        ?string $reason = null,
    ): void {
        $this->status = $status;
        $this->verificationResult = $verificationResult;
        $this->providerResponse = $providerResponse;
        $this->reason = $reason;
    }

    /**
     * 判断认证是否已过期
     */
    public function isExpired(): bool
    {
        return null !== $this->expireTime && $this->expireTime < new \DateTimeImmutable();
    }

    /**
     * 判断认证是否成功
     */
    public function isApproved(): bool
    {
        return AuthenticationStatus::APPROVED === $this->status && !$this->isExpired();
    }

    /**
     * 获取用户标识符，用于向后兼容
     */
    public function getUserIdentifier(): string
    {
        return $this->user->getUserIdentifier();
    }
}
