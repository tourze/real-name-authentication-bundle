<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\RealNameAuthenticationBundle\DependencyInjection\RealNameAuthenticationExtension;

/**
 * @internal
 */
#[CoversClass(RealNameAuthenticationExtension::class)]
final class RealNameAuthenticationExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private RealNameAuthenticationExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new RealNameAuthenticationExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoadDoesNotThrowException(): void
    {
        $configs = [];

        $this->expectNotToPerformAssertions();
        $this->extension->load($configs, $this->container);
    }

    public function testExtensionAlias(): void
    {
        $this->assertEquals('real_name_authentication', $this->extension->getAlias());
    }
}
