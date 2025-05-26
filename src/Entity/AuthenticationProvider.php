<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;
use Tourze\RealNameAuthenticationBundle\Repository\AuthenticationProviderRepository;

/**
 * 认证提供商
 *
 * 存储第三方认证服务提供商的配置信息
 */
#[ORM\Entity(repositoryClass: AuthenticationProviderRepository::class)]
#[ORM\Table(
    name: 'authentication_provider',
    options: ['comment' => '认证提供商表']
)]
#[ORM\Index(columns: ['type'], name: 'authentication_provider_idx_type')]
#[ORM\Index(columns: ['is_active'], name: 'authentication_provider_idx_is_active')]
#[UniqueEntity(fields: ['code'], message: '提供商代码已存在')]
class AuthenticationProvider implements Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '提供商名称'])]
    private string $name;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 50, unique: true, options: ['comment' => '提供商代码'])]
    private string $code;

    #[ORM\Column(type: Types::STRING, enumType: ProviderType::class, options: ['comment' => '提供商类型'])]
    private ProviderType $type;

    #[ORM\Column(type: Types::JSON, options: ['comment' => '支持的认证方式'])]
    private array $supportedMethods = [];

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'API接口地址'])]
    private string $apiEndpoint;

    #[ORM\Column(type: Types::JSON, options: ['comment' => '配置信息（加密存储）'])]
    private array $config = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级', 'default' => 0])]
    private int $priority = 0;

    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private DateTimeImmutable $createTime;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    private DateTimeImmutable $updateTime;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    private bool $valid = true;

    /**
     * @var Collection<int, AuthenticationResult>
     */
    #[ORM\OneToMany(targetEntity: AuthenticationResult::class, mappedBy: 'provider', fetch: 'EXTRA_LAZY')]
    private Collection $results;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createTime = new DateTimeImmutable();
        $this->updateTime = new DateTimeImmutable();
        $this->results = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->type->getLabel());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getType(): ProviderType
    {
        return $this->type;
    }

    public function setType(ProviderType $type): void
    {
        $this->type = $type;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getSupportedMethods(): array
    {
        return $this->supportedMethods;
    }

    public function setSupportedMethods(array $supportedMethods): void
    {
        $this->supportedMethods = $supportedMethods;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    public function setApiEndpoint(string $apiEndpoint): void
    {
        $this->apiEndpoint = $apiEndpoint;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
        $this->updateTime = new DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->updateTime = new DateTimeImmutable();
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
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

    /**
     * @return Collection<int, AuthenticationResult>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    /**
     * 判断是否支持指定的认证方式
     */
    public function supportsMethod(AuthenticationMethod $method): bool
    {
        return in_array($method->value, $this->supportedMethods);
    }

    /**
     * 获取配置项的值
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置项的值
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
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
