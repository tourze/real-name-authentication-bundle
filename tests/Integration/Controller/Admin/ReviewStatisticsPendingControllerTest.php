<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsPendingController;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Admin\ReviewStatisticsPendingController
 */
class ReviewStatisticsPendingControllerTest extends TestCase
{
    public function testControllerExists(): void
    {
        $this->assertTrue(class_exists(ReviewStatisticsPendingController::class));
    }

    public function testControllerConfiguration(): void
    {
        $manualReviewService = $this->createMock(ManualReviewService::class);
        $controller = new ReviewStatisticsPendingController($manualReviewService);
        
        $this->assertInstanceOf(ReviewStatisticsPendingController::class, $controller);
    }
}