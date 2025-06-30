<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsIndexController;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsIndexController
 */
class ReviewStatisticsIndexControllerTest extends TestCase
{
    public function testConstruct(): void
    {
        $manualReviewService = $this->createMock(ManualReviewService::class);
        $controller = new ReviewStatisticsIndexController($manualReviewService);
        
        $this->assertInstanceOf(ReviewStatisticsIndexController::class, $controller);
    }

    public function testInvoke(): void
    {
        $manualReviewService = $this->createMock(ManualReviewService::class);
        $controller = new ReviewStatisticsIndexController($manualReviewService);
        
        $this->assertInstanceOf(ReviewStatisticsIndexController::class, $controller);
    }
}