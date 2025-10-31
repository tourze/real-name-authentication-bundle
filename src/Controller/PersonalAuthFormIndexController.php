<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 个人认证方式选择页面控制器
 */
final class PersonalAuthFormIndexController extends AbstractController
{
    #[Route(path: '/auth/personal', name: 'auth_personal_index')]
    public function __invoke(): Response
    {
        $methods = [];
        foreach (AuthenticationMethod::cases() as $method) {
            if ($method->isPersonal()) {
                $methods[] = [
                    'value' => $method->value,
                    'label' => $method->getLabel(),
                    'requiredFields' => $method->getRequiredFields(),
                ];
            }
        }

        return $this->render('@RealNameAuthentication/personal_auth/index.html.twig', [
            'methods' => $methods,
        ]);
    }
}
