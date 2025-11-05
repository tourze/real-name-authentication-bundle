<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\DataFixtures;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * 测试用轻量用户实体
 *
 * 仅用于 DataFixtures 和测试环境，避免依赖具体的用户实现（如 BizUser）。
 * 实现最小化的 UserInterface 接口，满足实名认证功能的测试需求。
 */
class FixtureUser implements UserInterface, \Stringable
{
    private string $id;
    private string $userIdentifier;

    /**
     * @var array<string>
     */
    private array $roles = [];

    public function __construct(string $userIdentifier = 'test-user')
    {
        $this->id = uniqid('fixture_user_', true);
        $this->userIdentifier = $userIdentifier;
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        assert($this->userIdentifier !== '', 'User identifier must not be empty');

        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        // 确保至少有 ROLE_USER
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // 测试用户无需清理敏感信息
    }

    public function __toString(): string
    {
        return $this->userIdentifier;
    }
}
