<?php

namespace Tourze\RealNameAuthenticationBundle;

use BizUserBundle\BizUserBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;

/**
 * 实名认证Bundle
 */
class RealNameAuthenticationBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            BizUserBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            SecurityBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
        ];
    }
}
