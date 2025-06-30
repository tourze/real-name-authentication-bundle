<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * 认证状态查询页面控制器
 */
class PersonalAuthFormStatusController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService
    ) {
    }

    #[Route(path: '/auth/personal/status/{authId}', name: 'auth_personal_status')]
    public function __invoke(string $authId): Response
    {
        try {
            $authentication = $this->personalAuthService->checkAuthenticationStatus($authId);
            
            return $this->render('@RealNameAuthentication/personal_auth/status.html.twig', [
                'authentication' => $authentication,
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('danger', '认证记录不存在或查询失败');
            return $this->redirectToRoute('auth_personal_index');
        }
    }
}