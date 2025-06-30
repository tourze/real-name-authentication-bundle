<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * 控制器路由加载器
 *
 * 加载所有控制器的路由配置
 */
class AttributeControllerLoader extends Loader
{
    public function __construct(
        private readonly AnnotationClassLoader $controllerLoader
    ) {
        parent::__construct();
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        // Admin Controllers
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsIndexController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsPendingController::class));
        
        // API Controllers
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\Api\CheckAuthStatusController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\Api\GetAuthHistoryController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\Api\GetSupportedMethodsController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\Api\SubmitPersonalAuthController::class));
        
        // Form Controllers
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIndexController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIdCardTwoController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormCarrierThreeController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardThreeController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardFourController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormLivenessController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormStatusController::class));
        $collection->addCollection($this->controllerLoader->load(\Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormHistoryController::class));

        return $collection;
    }

    public function load($resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports($resource, ?string $type = null): bool
    {
        return 'real_name_authentication' === $type;
    }
}