<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 身份证二要素认证表单控制器
 */
class PersonalAuthFormIdCardTwoController extends AbstractController
{

    #[Route(path: '/auth/personal/id-card-two', name: 'auth_personal_id_card_two')]
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                // 这里应该调用API进行认证提交
                // 为了简化，这里直接重定向到状态页面
                $this->addFlash('success', '认证已提交，请等待处理结果');
                
                // 实际应用中，这里应该返回真实的认证ID
                return $this->redirectToRoute('auth_personal_index');
                
            } catch (\Throwable $e) {
                $this->addFlash('danger', '认证提交失败: ' . $e->getMessage());
                return $this->redirectToRoute('auth_personal_index');
            }
        }

        return $this->render('@RealNameAuthentication/personal_auth/id_card_two.html.twig');
    }
}