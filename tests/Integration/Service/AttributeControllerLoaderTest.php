<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Tourze\RealNameAuthenticationBundle\Service\AttributeControllerLoader;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Service\AttributeControllerLoader
 */
class AttributeControllerLoaderTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(AttributeControllerLoader::class));
    }

    public function testServiceConfiguration(): void
    {
        $controllerLoader = $this->createMock(AnnotationClassLoader::class);
        $service = new AttributeControllerLoader($controllerLoader);
        
        $this->assertInstanceOf(AttributeControllerLoader::class, $service);
    }
}