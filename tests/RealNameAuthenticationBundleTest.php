<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\RealNameAuthenticationBundle\RealNameAuthenticationBundle;

/**
 * @internal
 */
#[CoversClass(RealNameAuthenticationBundle::class)]
#[RunTestsInSeparateProcesses]
final class RealNameAuthenticationBundleTest extends AbstractBundleTestCase
{
}
