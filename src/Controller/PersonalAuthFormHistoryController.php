<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * 认证历史页面控制器
 */
class PersonalAuthFormHistoryController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService
    ) {
    }

    #[Route('/auth/personal/history', name: 'auth_personal_history')]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            $this->addFlash('danger', '请先登录');
            return $this->redirectToRoute('auth_personal_index');
        }

        try {
            $authentications = $this->personalAuthService->getAuthenticationHistory($user);
            
            return $this->render('@RealNameAuthentication/personal_auth/history.html.twig', [
                'authentications' => $authentications,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('danger', '查询认证历史失败');
            return $this->redirectToRoute('auth_personal_index');
        }
    }
}