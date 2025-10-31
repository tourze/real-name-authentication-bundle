<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 银行卡四要素认证表单控制器
 */
final class PersonalAuthFormBankCardFourController extends AbstractController
{
    #[Route(path: '/auth/personal/bank-card-four', name: 'auth_personal_bank_card_four')]
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

        return $this->render('@RealNameAuthentication/personal_auth/bank_card_four.html.twig');
    }
}
