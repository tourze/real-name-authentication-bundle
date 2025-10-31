<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RealNameAuthenticationBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testServiceInstance(): void
    {
        $service = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(AttributeControllerLoader::class, $service);
    }

    public function testAutoload(): void
    {
        $service = self::getService(AttributeControllerLoader::class);
        $routeCollection = $service->autoload();

        $this->assertInstanceOf(RouteCollection::class, $routeCollection);
        $this->assertGreaterThan(0, $routeCollection->count());

        // 验证加载了预期的路由数量（至少包含Admin、API、Form三类控制器）
        $this->assertGreaterThanOrEqual(10, $routeCollection->count());
    }

    public function testLoad(): void
    {
        $service = self::getService(AttributeControllerLoader::class);
        $routeCollection = $service->load('any-resource');

        $this->assertInstanceOf(RouteCollection::class, $routeCollection);
        $this->assertGreaterThan(0, $routeCollection->count());

        // load方法应该和autoload返回相同的结果
        $autoloadCollection = $service->autoload();
        $this->assertEquals($autoloadCollection->count(), $routeCollection->count());
    }

    public function testSupports(): void
    {
        $service = self::getService(AttributeControllerLoader::class);

        // supports方法应该总是返回false（根据实现）
        $this->assertFalse($service->supports('any-resource'));
        $this->assertFalse($service->supports('any-resource', 'any-type'));
        $this->assertFalse($service->supports(null));
    }
}
