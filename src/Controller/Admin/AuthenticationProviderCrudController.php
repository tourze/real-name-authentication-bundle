<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证提供商CRUD控制器
 */
class AuthenticationProviderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuthenticationProvider::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('认证提供商')
            ->setEntityLabelInPlural('认证提供商')
            ->setPageTitle('index', '认证提供商列表')
            ->setPageTitle('detail', '认证提供商详情')
            ->setPageTitle('new', '新建认证提供商')
            ->setPageTitle('edit', '编辑认证提供商')
            ->setHelp('index', '管理第三方认证服务提供商配置')
            ->setDefaultSort(['priority' => 'DESC', 'createTime' => 'ASC'])
            ->setSearchFields(['name', 'code', 'apiEndpoint'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield TextField::new('name', '提供商名称')
            ->setRequired(true);

        yield TextField::new('code', '提供商代码')
            ->setRequired(true)
            ->setHelp('唯一标识，用于程序调用');

        yield ChoiceField::new('type', '提供商类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ProviderType::class])
            ->setRequired(true)
            ->formatValue(function ($value) {
                return $value instanceof ProviderType ? $value->getLabel() : '';
            });

        yield ArrayField::new('supportedMethods', '支持的认证方式')
            ->setHelp('配置该提供商支持的认证方式列表');

        yield UrlField::new('apiEndpoint', 'API接口地址')
            ->setRequired(true);

        yield ArrayField::new('config', '配置信息')
            ->hideOnIndex()
            ->setHelp('包含API密钥、签名密钥等敏感信息');

        yield IntegerField::new('priority', '优先级')
            ->setHelp('数值越大优先级越高，范围0-100');

        yield BooleanField::new('isActive', '是否启用')
            ->renderAsSwitch(false);

        yield BooleanField::new('valid', '是否有效')
            ->renderAsSwitch(false);

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $typeChoices = [];
        foreach (ProviderType::cases() as $case) {
            $typeChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('name', '提供商名称'))
            ->add(TextFilter::new('code', '提供商代码'))
            ->add(ChoiceFilter::new('type', '提供商类型')->setChoices($typeChoices))
            ->add(BooleanFilter::new('isActive', '是否启用'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(NumericFilter::new('priority', '优先级'));
    }
}
