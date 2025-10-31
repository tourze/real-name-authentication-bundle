<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tourze\RealNameAuthenticationBundle\Entity\ImportRecord;
use Tourze\RealNameAuthenticationBundle\Enum\ImportRecordStatus;

/**
 * 导入记录CRUD控制器
 *
 * @extends AbstractCrudController<ImportRecord>
 */
#[AdminCrud(routePath: '/real-name-auth/import-record', routeName: 'real_name_auth_import_record')]
final class ImportRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ImportRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('导入记录')
            ->setEntityLabelInPlural('导入记录')
            ->setPageTitle('index', '导入记录管理')
            ->setPageTitle('detail', '导入记录详情')
            ->setPageTitle('new', '新建导入记录')
            ->setPageTitle('edit', '编辑导入记录')
            ->setHelp('index', '管理单条导入记录的处理结果和详细信息')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['errorMessage', 'remark'])
            ->setPaginatorPageSize(50)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('batch', '导入批次')
            ->setRequired(true)
            ->autocomplete()
            ->formatValue(function ($value, $entity) {
                if ($entity instanceof ImportRecord) {
                    return $entity->getBatch()->getOriginalFileName();
                }

                return '';
            })
        ;

        yield IntegerField::new('rowNumber', '行号')
            ->setRequired(true)
        ;

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ImportRecordStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof ImportRecordStatus ? $value->getLabel() : '';
            })
        ;

        yield ArrayField::new('rawData', '原始数据')
            ->hideOnIndex()
        ;

        yield ArrayField::new('processedData', '处理后数据')
            ->hideOnIndex()
        ;

        yield AssociationField::new('authentication', '关联认证记录')
            ->hideOnIndex()
            ->autocomplete()
            ->formatValue(function ($value, $entity) {
                if ($entity instanceof ImportRecord && null !== $entity->getAuthentication()) {
                    return sprintf('认证记录 %s', $entity->getAuthentication()->getId());
                }

                return '';
            })
        ;

        yield TextareaField::new('errorMessage', '错误信息')
            ->hideOnIndex()
            ->setNumOfRows(3)
        ;

        yield ArrayField::new('validationErrors', '验证错误详情')
            ->hideOnIndex()
        ;

        yield TextareaField::new('remark', '处理备注')
            ->hideOnIndex()
            ->setNumOfRows(3)
        ;

        yield IntegerField::new('processingTime', '处理时长(毫秒)')
            ->hideOnIndex()
            ->formatValue(function ($value) {
                if (null === $value || !is_numeric($value)) {
                    return '';
                }

                return $value . ' ms';
            })
        ;

        yield BooleanField::new('valid', '是否有效')
            ->renderAsSwitch(false)
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield TextField::new('resultSummary', '处理结果')
            ->hideOnForm()
            ->hideOnDetail()
            ->formatValue(function ($value, $entity) {
                if ($entity instanceof ImportRecord) {
                    $summary = $entity->getResultSummary();
                    $status = $entity->getStatus();

                    $badgeClass = match ($status) {
                        ImportRecordStatus::SUCCESS => 'success',
                        ImportRecordStatus::FAILED => 'danger',
                        ImportRecordStatus::SKIPPED => 'warning',
                        ImportRecordStatus::PENDING => 'secondary',
                    };

                    return sprintf('<span class="badge badge-%s">%s</span>', $badgeClass, $summary);
                }

                return '';
            })
            // ->setTemplateName('admin/field/status_badge.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewAuthenticationAction = Action::new('viewAuthentication', '查看认证记录', 'fas fa-eye')
            ->linkToCrudAction('viewAuthentication')
            ->setCssClass('btn btn-info')
            ->displayIf(function (ImportRecord $entity) {
                return null !== $entity->getAuthentication();
            })
        ;

        $retryAction = Action::new('retryRecord', '重新处理', 'fas fa-redo')
            ->linkToCrudAction('retryRecord')
            ->setCssClass('btn btn-warning')
            ->displayIf(function (ImportRecord $entity) {
                return $entity->isFailed() || $entity->isSkipped();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $viewAuthenticationAction)
            ->add(Crud::PAGE_INDEX, $retryAction)
            ->add(Crud::PAGE_DETAIL, $viewAuthenticationAction)
            ->add(Crud::PAGE_DETAIL, $retryAction)
            ->disable(Action::NEW) // 禁用新建，由导入流程创建
            ->disable(Action::EDIT) // 禁用编辑，状态由系统控制
            ->disable(Action::DELETE) // 禁用删除，保留审计记录
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('batch', '导入批次'))
            ->add(NumericFilter::new('rowNumber', '行号'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices([
                '待处理' => ImportRecordStatus::PENDING->value,
                '成功' => ImportRecordStatus::SUCCESS->value,
                '失败' => ImportRecordStatus::FAILED->value,
                '跳过' => ImportRecordStatus::SKIPPED->value,
            ]))
            ->add(TextFilter::new('errorMessage', '错误信息'))
            ->add(TextFilter::new('remark', '备注'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(NumericFilter::new('processingTime', '处理时长'))
        ;
    }

    /**
     * 查看关联的认证记录
     */
    #[AdminAction(routeName: 'import_record_view_authentication', routePath: '{entityId}/view-authentication')]
    public function viewAuthentication(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        // TODO: 实现跳转到认证记录详情
        $this->addFlash('info', '功能开发中');

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return $this->redirect($url);
    }

    /**
     * 重新处理记录
     */
    #[AdminAction(routeName: 'import_record_retry_record', routePath: '{entityId}/retry-record')]
    public function retryRecord(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        // TODO: 实现重新处理逻辑
        $this->addFlash('success', '重新处理请求已提交');

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        return $this->redirect($url);
    }
}
