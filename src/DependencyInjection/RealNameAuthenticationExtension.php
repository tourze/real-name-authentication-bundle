<?php

namespace Tourze\RealNameAuthenticationBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * 实名认证Bundle扩展配置
 */
final class RealNameAuthenticationExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
