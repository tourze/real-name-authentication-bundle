<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

/**
 * 实名认证记录CRUD控制器
 */
class RealNameAuthenticationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ManualReviewService $manualReviewService,
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RealNameAuthentication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('实名认证记录')
            ->setEntityLabelInPlural('实名认证记录')
            ->setPageTitle('index', '实名认证记录列表')
            ->setPageTitle('detail', '实名认证记录详情')
            ->setPageTitle('new', '新建实名认证记录')
            ->setPageTitle('edit', '编辑实名认证记录')
            ->setHelp('index', '查看和管理所有实名认证记录，支持人工审核')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'user.userIdentifier', 'reason'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('user', '用户')
            ->setMaxLength(20)
            ->formatValue(function ($value) {
                return $value ? $value->getUserIdentifier() : 'Unknown';
            })
            ->hideOnForm();

        yield ChoiceField::new('type', '认证类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AuthenticationType::class])
            ->formatValue(function ($value) {
                return $value instanceof AuthenticationType ? $value->getLabel() : '';
            });

        yield ChoiceField::new('method', '认证方式')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AuthenticationMethod::class])
            ->formatValue(function ($value) {
                return $value instanceof AuthenticationMethod ? $value->getLabel() : '';
            });

        yield ChoiceField::new('status', '认证状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AuthenticationStatus::class])
            ->formatValue(function ($value) {
                return $value instanceof AuthenticationStatus ? $value->getLabel() : '';
            });

        yield ArrayField::new('submittedData', '提交数据')
            ->hideOnIndex()
            ->hideOnForm();

        yield ArrayField::new('verificationResult', '验证结果')
            ->hideOnIndex()
            ->hideOnForm();

        yield ArrayField::new('providerResponse', '提供商响应')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextareaField::new('reason', '拒绝原因')
            ->hideOnIndex()
            ->setNumOfRows(3);

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield DateTimeField::new('expireTime', '过期时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield BooleanField::new('valid', '是否有效')
            ->renderAsSwitch(false);

        yield TextField::new('createdBy', '创建人')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('updatedBy', '更新人')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('createdFromIp', '创建IP')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('updatedFromIp', '更新IP')
            ->hideOnIndex()
            ->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        // 创建自定义审核操作
        $approveAction = Action::new('approve', '通过', 'fas fa-check')
            ->linkToCrudAction('approveAuthentication')
            ->setCssClass('btn btn-success')
            ->displayIf(function (RealNameAuthentication $entity) {
                return in_array($entity->getStatus(), [AuthenticationStatus::PENDING, AuthenticationStatus::PROCESSING]);
            });

        $rejectAction = Action::new('reject', '拒绝', 'fas fa-times')
            ->linkToCrudAction('rejectAuthentication')
            ->setCssClass('btn btn-danger')
            ->displayIf(function (RealNameAuthentication $entity) {
                return in_array($entity->getStatus(), [AuthenticationStatus::PENDING, AuthenticationStatus::PROCESSING]);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'approve', 'reject', Action::DELETE])
            ->disable(Action::NEW) // 禁用新建功能，认证记录通过API创建
            ->disable(Action::DELETE) // 禁用删除功能，只能设置无效
            ->disable(Action::EDIT); // 禁用编辑功能，认证记录不可修改
    }

    public function configureFilters(Filters $filters): Filters
    {
        $typeChoices = [];
        foreach (AuthenticationType::cases() as $case) {
            $typeChoices[$case->getLabel()] = $case->value;
        }

        $methodChoices = [];
        foreach (AuthenticationMethod::cases() as $case) {
            $methodChoices[$case->getLabel()] = $case->value;
        }

        $statusChoices = [];
        foreach (AuthenticationStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('user', '用户标识符'))
            ->add(ChoiceFilter::new('type', '认证类型')->setChoices($typeChoices))
            ->add(ChoiceFilter::new('method', '认证方式')->setChoices($methodChoices))
            ->add(ChoiceFilter::new('status', '认证状态')->setChoices($statusChoices))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('expireTime', '过期时间'));
    }

    /**
     * 通过认证申请
     */
    #[AdminAction('{entityId}/approveAuthentication', 'approveAuthentication')]
    public function approveAuthentication(AdminContext $context, Request $request): Response
    {
        /** @var RealNameAuthentication $authentication */
        $authentication = $context->getEntity()->getInstance();

        try {
            $reviewNote = $request->query->get('note', '管理员手动通过');
            $this->manualReviewService->approveAuthentication($authentication->getId(), $reviewNote);

            $this->addFlash('success', sprintf('认证申请 %s 已通过审核', $authentication->getId()));
        } catch (\Exception $e) {
            $this->addFlash('danger', '审核失败: ' . $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    /**
     * 拒绝认证申请
     */
    #[AdminAction('{entityId}/rejectAuthentication', 'rejectAuthentication', methods: ['GET', 'POST'])]
    public function rejectAuthentication(AdminContext $context, Request $request): Response
    {
        /** @var RealNameAuthentication $authentication */
        $authentication = $context->getEntity()->getInstance();

        // 如果是GET请求，显示拒绝原因输入表单
        if ($request->isMethod('GET')) {
            return $this->render('@RealNameAuthentication/admin/reject_form.html.twig', [
                'authentication' => $authentication,
                'back_url' => $this->generateBackUrl($context),
            ]);
        }

        // 处理POST请求
        try {
            $reason = $request->request->get('reason');
            $reviewNote = $request->request->get('review_note', '管理员手动拒绝');

            if (empty($reason)) {
                throw new \InvalidArgumentException('请提供拒绝原因');
            }

            $this->manualReviewService->rejectAuthentication($authentication->getId(), $reason, $reviewNote);

            $this->addFlash('success', sprintf('认证申请 %s 已拒绝', $authentication->getId()));
        } catch (\Exception $e) {
            $this->addFlash('danger', '审核失败: ' . $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    /**
     * 重定向到列表页面
     */
    private function redirectToIndex(): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * 生成返回URL
     */
    private function generateBackUrl(AdminContext $context): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($context->getEntity()->getPrimaryKeyValue())
            ->generateUrl();
    }
}
