<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 活体检测认证表单控制器
 */
final class PersonalAuthFormLivenessController extends AbstractController
{
    #[Route(path: '/auth/personal/liveness', name: 'auth_personal_liveness', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $this->addFlash('success', '认证已提交，请等待处理结果');

                return $this->redirectToRoute('auth_personal_index');
            } catch (\Throwable $e) {
                $this->addFlash('danger', '认证提交失败: ' . $e->getMessage());

                return $this->redirectToRoute('auth_personal_index');
            }
        }

        return $this->render('@RealNameAuthentication/personal_auth/liveness.html.twig');
    }
}
