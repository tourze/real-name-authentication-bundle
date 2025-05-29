<?php

namespace Tourze\RealNameAuthenticationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;

/**
 * 个人认证表单控制器
 */
#[Route('/auth/personal', name: 'auth_personal_')]
class PersonalAuthFormController extends AbstractController
{
    public function __construct(
        private readonly PersonalAuthenticationService $personalAuthService
    ) {
    }

    /**
     * 认证方式选择页面
     */
    #[Route('/', name: 'index')]
    public function index(): Response
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

    /**
     * 身份证二要素认证表单
     */
    #[Route('/id-card-two', name: 'id_card_two')]
    public function idCardTwo(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // 处理表单提交
            return $this->handleFormSubmission($request, AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
        }

        return $this->render('@RealNameAuthentication/personal_auth/id_card_two.html.twig');
    }

    /**
     * 运营商三要素认证表单
     */
    #[Route('/carrier-three', name: 'carrier_three')]
    public function carrierThree(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleFormSubmission($request, AuthenticationMethod::CARRIER_THREE_ELEMENTS);
        }

        return $this->render('@RealNameAuthentication/personal_auth/carrier_three.html.twig');
    }

    /**
     * 银行卡三要素认证表单
     */
    #[Route('/bank-card-three', name: 'bank_card_three')]
    public function bankCardThree(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleFormSubmission($request, AuthenticationMethod::BANK_CARD_THREE_ELEMENTS);
        }

        return $this->render('@RealNameAuthentication/personal_auth/bank_card_three.html.twig');
    }

    /**
     * 银行卡四要素认证表单
     */
    #[Route('/bank-card-four', name: 'bank_card_four')]
    public function bankCardFour(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleFormSubmission($request, AuthenticationMethod::BANK_CARD_FOUR_ELEMENTS);
        }

        return $this->render('@RealNameAuthentication/personal_auth/bank_card_four.html.twig');
    }

    /**
     * 活体检测认证表单
     */
    #[Route('/liveness', name: 'liveness')]
    public function liveness(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handleFormSubmission($request, AuthenticationMethod::LIVENESS_DETECTION);
        }

        return $this->render('@RealNameAuthentication/personal_auth/liveness.html.twig');
    }

    /**
     * 认证状态查询页面
     */
    #[Route('/status/{authId}', name: 'status')]
    public function status(string $authId): Response
    {
        try {
            $authentication = $this->personalAuthService->checkAuthenticationStatus($authId);
            
            return $this->render('@RealNameAuthentication/personal_auth/status.html.twig', [
                'authentication' => $authentication,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', '认证记录不存在或查询失败');
            return $this->redirectToRoute('auth_personal_index');
        }
    }

    /**
     * 认证历史页面
     */
    #[Route('/history', name: 'history')]
    public function history(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', '请先登录');
            return $this->redirectToRoute('auth_personal_index');
        }

        try {
            $authentications = $this->personalAuthService->getAuthenticationHistory($user);
            
            return $this->render('@RealNameAuthentication/personal_auth/history.html.twig', [
                'authentications' => $authentications,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', '查询认证历史失败');
            return $this->redirectToRoute('auth_personal_index');
        }
    }

    /**
     * 处理表单提交
     */
    private function handleFormSubmission(Request $request, AuthenticationMethod $method): Response
    {
        try {
            // 这里应该调用API进行认证提交
            // 为了简化，这里直接重定向到状态页面
            $this->addFlash('success', '认证已提交，请等待处理结果');
            
            // 实际应用中，这里应该返回真实的认证ID
            return $this->redirectToRoute('auth_personal_index');
            
        } catch (\Exception $e) {
            $this->addFlash('danger', '认证提交失败: ' . $e->getMessage());
            return $this->redirectToRoute('auth_personal_index');
        }
    }
} 