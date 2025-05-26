<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * 审核统计控制器
 */
#[Route('/admin/auth/statistics', name: 'admin_auth_statistics_')]
class ReviewStatisticsController extends AbstractController
{
    public function __construct(
        private readonly ManualReviewService $manualReviewService
    ) {
    }

    /**
     * 审核统计首页
     */
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        // 获取查询参数
        $startDate = $request->query->get('start_date', (new DateTimeImmutable('-30 days'))->format('Y-m-d'));
        $endDate = $request->query->get('end_date', (new DateTimeImmutable())->format('Y-m-d'));

        try {
            $startDateTime = new DateTimeImmutable($startDate . ' 00:00:00');
            $endDateTime = new DateTimeImmutable($endDate . ' 23:59:59');
        } catch (\Exception) {
            $startDateTime = new DateTimeImmutable('-30 days');
            $endDateTime = new DateTimeImmutable();
        }

        // 获取统计数据
        $statistics = $this->manualReviewService->getReviewStatistics($startDateTime, $endDateTime);
        
        // 获取待审核列表
        $pendingAuthentications = $this->manualReviewService->getPendingAuthentications(10);

        return $this->render('@RealNameAuthentication/admin/statistics.html.twig', [
            'statistics' => $statistics,
            'pending_authentications' => $pendingAuthentications,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'date_range_days' => $startDateTime->diff($endDateTime)->days + 1,
        ]);
    }

    /**
     * 待审核列表
     */
    #[Route('/pending', name: 'pending')]
    public function pending(Request $request): Response
    {
        $limit = $request->query->getInt('limit', 50);
        $pendingAuthentications = $this->manualReviewService->getPendingAuthentications($limit);

        return $this->render('@RealNameAuthentication/admin/pending_list.html.twig', [
            'authentications' => $pendingAuthentications,
            'total_count' => count($pendingAuthentications),
        ]);
    }
} 