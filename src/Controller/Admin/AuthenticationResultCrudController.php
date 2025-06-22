<?php

namespace Tourze\RealNameAuthenticationBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationResult;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;

/**
 * 认证结果CRUD控制器
 */
class AuthenticationResultCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AuthenticationResult::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('认证结果')
            ->setEntityLabelInPlural('认证结果')
            ->setPageTitle('index', '认证结果列表')
            ->setPageTitle('detail', '认证结果详情')
            ->setPageTitle('new', '新建认证结果')
            ->setPageTitle('edit', '编辑认证结果')
            ->setHelp('index', '查看和管理所有认证结果记录')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['requestId', 'errorCode', 'errorMessage'])
            ->setPaginatorPageSize(30);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm();

        yield AssociationField::new('authentication', '认证记录')
            ->setFormTypeOptions([
                'choice_label' => function (RealNameAuthentication $auth) {
                    return sprintf('%s - %s (%s)',
                        $auth->getUser()->getUserIdentifier(),
                        $auth->getMethod()->getLabel(),
                        $auth->getStatus()->getLabel()
                    );
                }
            ])
            ->formatValue(function ($value) {
                if ($value instanceof RealNameAuthentication) {
                    return sprintf('%s - %s', $value->getUser()->getUserIdentifier(), $value->getMethod()->getLabel());
                }
                return '';
            });

        yield AssociationField::new('provider', '认证提供商')
            ->setFormTypeOptions([
                'choice_label' => 'name'
            ])
            ->formatValue(function ($value) {
                return $value instanceof AuthenticationProvider ? $value->getName() : '';
            });

        yield TextField::new('requestId', '请求ID')
            ->setMaxLength(30);

        yield BooleanField::new('isSuccess', '是否成功')
            ->renderAsSwitch(false);

        yield NumberField::new('confidence', '置信度')
            ->setNumDecimals(3)
            ->hideOnIndex()
            ->setHelp('0-1之间的数值，表示认证结果的可信度');

        yield ArrayField::new('responseData', '响应数据')
            ->hideOnIndex()
            ->hideOnForm();

        yield TextField::new('errorCode', '错误代码')
            ->hideOnIndex();

        yield TextareaField::new('errorMessage', '错误消息')
            ->hideOnIndex()
            ->setNumOfRows(3);

        yield IntegerField::new('processingTime', '处理时间(ms)')
            ->setHelp('认证处理耗时，单位毫秒');

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss');

        yield BooleanField::new('valid', '是否有效')
            ->renderAsSwitch(false);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])
            ->disable(Action::NEW) // 禁用新建功能，认证结果由系统生成
            ->disable(Action::DELETE) // 禁用删除功能，只能设置无效
            ->disable(Action::EDIT); // 禁用编辑功能，认证结果不可修改
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('authentication', '认证记录'))
            ->add(EntityFilter::new('provider', '认证提供商'))
            ->add(TextFilter::new('requestId', '请求ID'))
            ->add(BooleanFilter::new('isSuccess', '是否成功'))
            ->add(TextFilter::new('errorCode', '错误代码'))
            ->add(NumericFilter::new('confidence', '置信度'))
            ->add(NumericFilter::new('processingTime', '处理时间'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(DateTimeFilter::new('createTime', '创建时间'));
    }
}
