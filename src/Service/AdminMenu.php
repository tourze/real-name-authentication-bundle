<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;

/**
 * 实名认证菜单服务
 */
class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private readonly LinkGeneratorInterface $linkGenerator,
    )
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if ($item->getChild('实名认证') === null) {
            $item->addChild('实名认证');
        }

        $authMenu = $item->getChild('实名认证');

        // 认证记录菜单
        $authMenu->addChild('认证记录')
            ->setUri($this->linkGenerator->getCurdListPage(RealNameAuthentication::class))
            ->setAttribute('icon', 'fas fa-id-card');

        // 认证提供商菜单
        $authMenu->addChild('认证提供商')
            ->setUri($this->linkGenerator->getCurdListPage(AuthenticationProvider::class))
            ->setAttribute('icon', 'fas fa-server');
    }
}
