<?php

namespace Tourze\RealNameAuthenticationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

/**
 * 实名认证Bundle
 */
class RealNameAuthenticationBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle::class => ['all' => true],
            \Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle::class => ['all' => true],
            \Tourze\DoctrineUserBundle\DoctrineUserBundle::class => ['all' => true],
        ];
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
