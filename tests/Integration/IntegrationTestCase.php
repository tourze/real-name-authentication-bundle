<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\RealNameAuthenticationBundle\RealNameAuthenticationBundle;
use Tourze\RealNameAuthenticationBundle\Tests\Fixtures\TestUser;

/**
 * 集成测试基类
 */
abstract class IntegrationTestCase extends TestCase
{
    protected IntegrationTestKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = new IntegrationTestKernel(
            'test',
            true,
            [
                FrameworkBundle::class => ['all' => true],
                DoctrineBundle::class => ['all' => true],
                SecurityBundle::class => ['all' => true],
                RealNameAuthenticationBundle::class => ['all' => true],
            ],
            [
                'Tourze\RealNameAuthenticationBundle\Entity' => dirname(__DIR__, 2) . '/src/Entity',
                'Tourze\RealNameAuthenticationBundle\Tests\Fixtures' => dirname(__DIR__) . '/Fixtures',
            ],
            function (ContainerBuilder $container) {
                // 配置 Doctrine 的 resolve_target_entities
                if ($container->hasExtension('doctrine')) {
                    $container->prependExtensionConfig('doctrine', [
                        'orm' => [
                            'resolve_target_entities' => [
                                UserInterface::class => TestUser::class,
                            ],
                        ],
                    ]);
                }
            }
        );

        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        parent::tearDown();
    }

    /**
     * 获取容器服务
     */
    protected function getService(string $id): ?object
    {
        return $this->kernel->getContainer()->get($id);
    }
}