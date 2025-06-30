<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 获取支持的认证方式控制器
 */
class GetSupportedMethodsController extends AbstractController
{
    #[Route(path: '/api/auth/personal/methods', name: 'api_auth_personal_methods', methods: ['GET'])]
    public function __invoke(): JsonResponse
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

        return new JsonResponse([
            'success' => true,
            'data' => $methods
        ]);
    }
}