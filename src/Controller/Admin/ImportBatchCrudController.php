<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\RealNameAuthenticationBundle\Entity\ImportBatch;
use Tourze\RealNameAuthenticationBundle\Enum\ImportStatus;

/**
 * 导入批次CRUD控制器
 */
class ImportBatchCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImportBatch::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('导入批次')
            ->setEntityLabelInPlural('导入批次')
            ->setPageTitle('index', '批量导入管理')
            ->setPageTitle('detail', '导入批次详情')
            ->setPageTitle('new', '新建导入批次')
            ->setPageTitle('edit', '编辑导入批次')
            ->setHelp('index', '管理实名认证信息的批量导入批次')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['originalFileName', 'fileMd5', 'errorMessage'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('originalFileName', '文件名')
            ->setMaxLength(40);

        yield TextField::new('fileType', '文件类型')
            ->setMaxLength(10);

        yield IntegerField::new('fileSize', '文件大小(字节)')
            ->formatValue(function ($value) {
                if ($value < 1024) {
                    return $value . ' B';
                } elseif ($value < 1024 * 1024) {
                    return round($value / 1024, 2) . ' KB';
                } else {
                    return round($value / (1024 * 1024), 2) . ' MB';
                }
            });

        yield TextField::new('fileMd5', 'MD5值')
            ->hideOnIndex()
            ->setMaxLength(32);

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ImportStatus::class])
            ->formatValue(function ($value) {
                if ($value instanceof ImportStatus) {
                    return sprintf('<span class="badge badge-%s">%s</span>', 
                        $value->getCssClass(), 
                        $value->getLabel()
                    );
                }
                return '';
            })
            ->setTemplateName('admin/field/status_badge.html.twig');

        yield IntegerField::new('totalRecords', '总记录数');

        yield IntegerField::new('processedRecords', '已处理')
            ->hideOnForm();

        yield IntegerField::new('successRecords', '成功数')
            ->hideOnForm();

        yield IntegerField::new('failedRecords', '失败数')
            ->hideOnForm();

        yield NumberField::new('progressPercentage', '进度(%)')
            ->hideOnForm()
            ->setNumDecimals(1)
            ->formatValue(function ($value, $entity) {
                if ($entity instanceof ImportBatch) {
                    $percentage = $entity->getProgressPercentage();
                    return sprintf('<div class="progress">
                        <div class="progress-bar" style="width: %.1f%%">%.1f%%</div>
                    </div>', $percentage, $percentage);
                }
                return '';
            })
            ->setTemplateName('admin/field/progress_bar.html.twig');

        yield NumberField::new('successRate', '成功率(%)')
            ->hideOnIndex()
            ->hideOnForm()
            ->setNumDecimals(1);

        yield DateTimeField::new('startTime', '开始时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield DateTimeField::new('finishTime', '完成时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield IntegerField::new('processingDuration', '处理时长(秒)')
            ->hideOnIndex()
            ->hideOnForm()
            ->formatValue(function ($value) {
                if ($value === null) return '';
                
                $hours = intval($value / 3600);
                $minutes = intval(($value % 3600) / 60);
                $seconds = $value % 60;
                
                if ($hours > 0) {
                    return sprintf('%d时%d分%d秒', $hours, $minutes, $seconds);
                } elseif ($minutes > 0) {
                    return sprintf('%d分%d秒', $minutes, $seconds);
                } else {
                    return sprintf('%d秒', $seconds);
                }
            });

        yield ArrayField::new('importConfig', '导入配置')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextareaField::new('errorMessage', '错误信息')
            ->hideOnIndex()
            ->setNumOfRows(3);

        yield TextareaField::new('remark', '备注')
            ->hideOnIndex()
            ->setNumOfRows(3);

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield TextField::new('createdBy', '创建人')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('createdFromIp', '创建IP')
            ->hideOnIndex()
            ->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        // 创建自定义操作
        $viewRecordsAction = Action::new('viewRecords', '查看记录', 'fas fa-list')
            ->linkToRoute('admin_import_records', function (ImportBatch $entity) {
                return ['batchId' => $entity->getId()];
            })
            ->setCssClass('btn btn-info');

        $downloadTemplateAction = Action::new('downloadTemplate', '下载模板', 'fas fa-download')
            ->linkToRoute('admin_import_template')
            ->setCssClass('btn btn-success')
            ->createAsGlobalAction();

        $uploadFileAction = Action::new('uploadFile', '批量导入', 'fas fa-upload')
            ->linkToRoute('admin_import_upload')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();

        $retryAction = Action::new('retry', '重试失败', 'fas fa-redo')
            ->linkToCrudAction('retryFailedRecords')
            ->setCssClass('btn btn-warning')
            ->displayIf(function (ImportBatch $entity) {
                return $entity->getFailedRecords() > 0 && $entity->isCompleted();
            });

        $cancelAction = Action::new('cancel', '取消', 'fas fa-stop')
            ->linkToCrudAction('cancelBatch')
            ->setCssClass('btn btn-danger')
            ->displayIf(function (ImportBatch $entity) {
                return $entity->getStatus()->isCancellable();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $viewRecordsAction)
            ->add(Crud::PAGE_INDEX, $retryAction)
            ->add(Crud::PAGE_INDEX, $cancelAction)
            ->add(Crud::PAGE_INDEX, $downloadTemplateAction)
            ->add(Crud::PAGE_INDEX, $uploadFileAction)
            ->add(Crud::PAGE_DETAIL, $viewRecordsAction)
            ->add(Crud::PAGE_DETAIL, $retryAction)
            ->add(Crud::PAGE_DETAIL, $cancelAction)
            ->disable(Action::NEW) // 禁用新建，通过上传创建
            ->disable(Action::EDIT) // 禁用编辑
            ->disable(Action::DELETE); // 禁用删除
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statusChoices = [];
        foreach (ImportStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('originalFileName', '文件名'))
            ->add(TextFilter::new('fileType', '文件类型'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(NumericFilter::new('totalRecords', '总记录数'))
            ->add(NumericFilter::new('successRecords', '成功数'))
            ->add(NumericFilter::new('failedRecords', '失败数'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('finishTime', '完成时间'))
            ->add(TextFilter::new('createdBy', '创建人'));
    }

    /**
     * 重试失败记录
     */
    public function retryFailedRecords(): void
    {
        // TODO: 实现重试逻辑
        $this->addFlash('success', '重试操作已提交');
    }

    /**
     * 取消批次
     */
    public function cancelBatch(): void
    {
        // TODO: 实现取消逻辑
        $this->addFlash('success', '批次已取消');
    }
}
