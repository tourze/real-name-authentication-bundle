<?php

namespace Tourze\RealNameAuthenticationBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsIndexController;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsPendingController;
use Tourze\RealNameAuthenticationBundle\Controller\Api\CheckAuthStatusController;
use Tourze\RealNameAuthenticationBundle\Controller\Api\GetAuthHistoryController;
use Tourze\RealNameAuthenticationBundle\Controller\Api\GetSupportedMethodsController;
use Tourze\RealNameAuthenticationBundle\Controller\Api\SubmitPersonalAuthController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardFourController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormBankCardThreeController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormCarrierThreeController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormHistoryController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIdCardTwoController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormIndexController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormLivenessController;
use Tourze\RealNameAuthenticationBundle\Controller\PersonalAuthFormStatusController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

/**
 * 控制器路由加载器
 *
 * 加载所有控制器的路由配置
 */
#[AutoconfigureTag(name: 'routing.loader')]
final class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();

        // Admin Controllers
        $collection->addCollection($this->controllerLoader->load(ReviewStatisticsIndexController::class));
        $collection->addCollection($this->controllerLoader->load(ReviewStatisticsPendingController::class));

        // API Controllers
        $collection->addCollection($this->controllerLoader->load(CheckAuthStatusController::class));
        $collection->addCollection($this->controllerLoader->load(GetAuthHistoryController::class));
        $collection->addCollection($this->controllerLoader->load(GetSupportedMethodsController::class));
        $collection->addCollection($this->controllerLoader->load(SubmitPersonalAuthController::class));

        // Form Controllers
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormIndexController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormIdCardTwoController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormCarrierThreeController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormBankCardThreeController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormBankCardFourController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormLivenessController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormStatusController::class));
        $collection->addCollection($this->controllerLoader->load(PersonalAuthFormHistoryController::class));

        return $collection;
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }
}
