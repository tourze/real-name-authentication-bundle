<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * 审核统计首页控制器
 */
final class ReviewStatisticsIndexController extends AbstractController
{
    public function __construct(
        private readonly ManualReviewService $manualReviewService,
    ) {
    }

    #[Route(path: '/admin/auth/statistics', name: 'admin_auth_statistics_index', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        // 获取查询参数
        $defaultStartDate = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
        $defaultEndDate = (new \DateTimeImmutable())->format('Y-m-d');
        $startDate = $request->query->get('start_date', $defaultStartDate);
        $endDate = $request->query->get('end_date', $defaultEndDate);

        try {
            $startDateTime = new \DateTimeImmutable($startDate . ' 00:00:00');
            $endDateTime = new \DateTimeImmutable($endDate . ' 23:59:59');
        } catch (\Exception) {
            $startDateTime = new \DateTimeImmutable('-30 days');
            $endDateTime = new \DateTimeImmutable();
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
            'date_range_days' => (int) $startDateTime->diff($endDateTime)->days + 1,
        ]);
    }
}
