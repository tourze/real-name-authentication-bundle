<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface&MockObject $linkGenerator;

    private ItemInterface&MockObject $item;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
        $this->item = $this->createMock(ItemInterface::class);
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    public function testImplementsMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testInvokeShouldBeCallable(): void
    {
        $reflection = new \ReflectionClass(AdminMenu::class);
        $this->assertTrue($reflection->hasMethod('__invoke'));
    }

    public function testInvoke(): void
    {
        $this->linkGenerator->expects($this->exactly(5))
            ->method('getCurdListPage')
            ->with(self::callback(function ($class) {
                return 'Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication' === $class
                    || 'Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider' === $class
                    || 'Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult' === $class
                    || 'Tourze\RealNameAuthenticationBundle\Entity\ImportBatch' === $class
                    || 'Tourze\RealNameAuthenticationBundle\Entity\ImportRecord' === $class;
            }))
            ->willReturnOnConsecutiveCalls(
                '/admin/real-name-authentications',
                '/admin/authentication-providers',
                '/admin/authentication-results',
                '/admin/import-batches',
                '/admin/import-records'
            )
        ;

        $authenticationMenu = $this->createMock(ItemInterface::class);
        $authenticationListItem = $this->createMock(ItemInterface::class);
        $providerListItem = $this->createMock(ItemInterface::class);
        $resultListItem = $this->createMock(ItemInterface::class);
        $batchListItem = $this->createMock(ItemInterface::class);
        $recordListItem = $this->createMock(ItemInterface::class);

        $this->item->expects($this->exactly(2))
            ->method('getChild')
            ->with('实名认证')
            ->willReturnOnConsecutiveCalls(null, $authenticationMenu)
        ;

        $this->item->expects($this->once())
            ->method('addChild')
            ->with('实名认证')
            ->willReturn($authenticationMenu)
        ;

        $authenticationMenu->expects($this->exactly(5))
            ->method('addChild')
            ->willReturnCallback(function (string $label) use ($authenticationListItem, $providerListItem, $resultListItem, $batchListItem, $recordListItem) {
                $item = match ($label) {
                    '认证记录' => $authenticationListItem,
                    '认证提供商' => $providerListItem,
                    '认证结果' => $resultListItem,
                    '导入批次' => $batchListItem,
                    '导入记录' => $recordListItem,
                    default => throw new InvalidAuthenticationDataException('Unexpected menu label: ' . $label),
                };

                // Configure the mock to return self for method chaining
                $item->method('setUri')->willReturnSelf();
                $item->method('setAttribute')->willReturnSelf();

                return $item;
            })
        ;

        $authenticationListItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/real-name-authentications')
            ->willReturnSelf()
        ;

        $authenticationListItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-id-card')
            ->willReturnSelf()
        ;

        $providerListItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/authentication-providers')
            ->willReturnSelf()
        ;

        $providerListItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-server')
            ->willReturnSelf()
        ;

        $resultListItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/authentication-results')
            ->willReturnSelf()
        ;

        $resultListItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-check-circle')
            ->willReturnSelf()
        ;

        $batchListItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/import-batches')
            ->willReturnSelf()
        ;

        $batchListItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-upload')
            ->willReturnSelf()
        ;

        $recordListItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/import-records')
            ->willReturnSelf()
        ;

        $recordListItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-list-ul')
            ->willReturnSelf()
        ;

        ($this->adminMenu)($this->item);
    }
}
