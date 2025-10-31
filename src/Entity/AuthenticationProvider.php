<?php

namespace Tourze\RealNameAuthenticationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
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
#[UniqueEntity(fields: ['code'], message: '提供商代码已存在')]
class AuthenticationProvider implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\CustomIdGenerator]
    #[ORM\Column(name: 'id', type: Types::STRING, length: 36, options: ['comment' => '主键ID'])]
    #[Assert\Length(max: 36)]
    private string $id;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 100, options: ['comment' => '提供商名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 50, unique: true, options: ['comment' => '提供商代码'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $code;

    #[ORM\Column(name: 'type', type: Types::STRING, enumType: ProviderType::class, options: ['comment' => '提供商类型'])]
    #[IndexColumn]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ProviderType::class, 'cases'])]
    private ProviderType $type;

    /** @var array<string> */
    #[ORM\Column(name: 'supported_methods', type: Types::JSON, options: ['comment' => '支持的认证方式'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'array')]
    private array $supportedMethods = [];

    #[ORM\Column(name: 'api_endpoint', type: Types::STRING, length: 255, options: ['comment' => 'API接口地址'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Url]
    private string $apiEndpoint;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'config', type: Types::JSON, options: ['comment' => '配置信息（加密存储）'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'array')]
    private array $config = [];

    #[ORM\Column(name: 'active', type: Types::BOOLEAN, options: ['comment' => '是否启用', 'default' => true])]
    #[IndexColumn]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private bool $active = true;

    #[ORM\Column(name: 'priority', type: Types::INTEGER, options: ['comment' => '优先级', 'default' => 0])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'int')]
    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $priority = 0;

    #[ORM\Column(name: 'valid', type: Types::BOOLEAN, options: ['comment' => '是否有效', 'default' => true])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private bool $valid = true;

    /**
     * @var Collection<int, AuthenticationResult>
     */
    #[ORM\OneToMany(targetEntity: AuthenticationResult::class, mappedBy: 'provider', fetch: 'EXTRA_LAZY')]
    private Collection $results;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
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
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getType(): ProviderType
    {
        return $this->type;
    }

    public function setType(ProviderType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<string>
     */
    public function getSupportedMethods(): array
    {
        return $this->supportedMethods;
    }

    /**
     * @param array<string> $supportedMethods
     */
    public function setSupportedMethods(array $supportedMethods): void
    {
        $this->supportedMethods = $supportedMethods;
    }

    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    public function setApiEndpoint(string $apiEndpoint): void
    {
        $this->apiEndpoint = $apiEndpoint;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
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
        return in_array($method->value, $this->supportedMethods, true);
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
    }
}
