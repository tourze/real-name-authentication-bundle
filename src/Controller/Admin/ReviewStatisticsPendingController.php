<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * 待审核列表控制器
 */
class ReviewStatisticsPendingController extends AbstractController
{
    public function __construct(
        private readonly ManualReviewService $manualReviewService
    ) {
    }

    #[Route('/admin/auth/statistics/pending', name: 'admin_auth_statistics_pending')]
    public function __invoke(Request $request): Response
    {
        $limit = $request->query->getInt('limit', 50);
        $pendingAuthentications = $this->manualReviewService->getPendingAuthentications($limit);

        return $this->render('@RealNameAuthentication/admin/pending_list.html.twig', [
            'authentications' => $pendingAuthentications,
            'total_count' => count($pendingAuthentications),
        ]);
    }
}