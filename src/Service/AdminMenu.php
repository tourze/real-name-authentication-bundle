<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;

/**
 * 实名认证菜单服务
 */
#[Autoconfigure(public: true)]
final readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('实名认证')) {
            $item->addChild('实名认证');
        }

        $authMenu = $item->getChild('实名认证');
        if (null === $authMenu) {
            return;
        }

        // 认证记录菜单
        $authMenu->addChild('认证记录')
            ->setUri($this->linkGenerator->getCurdListPage(RealNameAuthentication::class))
            ->setAttribute('icon', 'fas fa-id-card')
        ;

        // 认证提供商菜单
        $authMenu->addChild('认证提供商')
            ->setUri($this->linkGenerator->getCurdListPage(AuthenticationProvider::class))
            ->setAttribute('icon', 'fas fa-server')
        ;

        // 认证结果菜单
        $authMenu->addChild('认证结果')
            ->setUri($this->linkGenerator->getCurdListPage(AuthenticationResult::class))
            ->setAttribute('icon', 'fas fa-check-circle')
        ;

        // 导入批次菜单
        $authMenu->addChild('导入批次')
            ->setUri($this->linkGenerator->getCurdListPage(ImportBatch::class))
            ->setAttribute('icon', 'fas fa-upload')
        ;

        // 导入记录菜单
        $authMenu->addChild('导入记录')
            ->setUri($this->linkGenerator->getCurdListPage(ImportRecord::class))
            ->setAttribute('icon', 'fas fa-list-ul')
        ;
    }
}
