<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\RealNameAuthenticationBundle\Controller\Admin\ImportBatchCrudController;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;

/**
 * @covers \Tourze\RealNameAuthenticationBundle\Controller\Admin\ImportBatchCrudController
 */
class ImportBatchCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertSame(
            ImportBatch::class,
            ImportBatchCrudController::getEntityFqcn()
        );
    }

    public function testControllerConfiguration(): void
    {
        $controller = new ImportBatchCrudController();
        
        $this->assertInstanceOf(ImportBatchCrudController::class, $controller);
    }
}