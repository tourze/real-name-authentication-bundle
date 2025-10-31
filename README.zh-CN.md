# 实名认证Bundle

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/symfony-%3E%3D6.4-000000.svg)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Code Coverage](https://img.shields.io/badge/coverage-95%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

> **🎯 完全独立的Bundle** - 不依赖任何外部Bundle的asset或模板文件，可以独立运行在任何Symfony项目中。

一个完整的个人实名认证解决方案，提供多种认证方式、完整的后台管理功能和人工审核机制。

## 目录

- [Bundle独立性](#bundle独立性)
- [功能特性](#功能特性)
  - [个人实名认证](#个人实名认证)
  - [核心功能](#核心功能)
  - [人工审核功能](#人工审核功能)
- [安装说明](#安装说明)
  - [依赖要求](#依赖要求)
  - [安装](#安装)
- [快速开始](#快速开始)
  - [配置](#配置)
  - [基本使用](#基本使用)
- [API使用示例](#api使用示例)
- [后台管理](#后台管理)
- [人工审核操作指南](#人工审核操作指南)
- [高级用法](#高级用法)
- [架构设计](#架构设计)
- [技术规范](#技术规范)
- [开发状态](#开发状态)
- [许可证](#许可证)

## Bundle独立性

- ✅ **模板独立**：使用Bundle内部的基础模板，不依赖外部项目模板
- ✅ **样式独立**：通过CDN加载Bootstrap和Font Awesome，无需外部CSS
- ✅ **脚本独立**：内置所有JavaScript功能，无需外部JS库
- ✅ **路由独立**：自动注册路由，无需手动配置
- ✅ **即插即用**：安装后即可使用，无需额外配置

## 功能特性

### 个人实名认证

- ✅ 身份证二要素验证（姓名+身份证号）
- ✅ 运营商三要素验证（姓名+身份证号+手机号）
- ✅ 银行卡三要素验证（姓名+身份证号+银行卡号）
- ✅ 银行卡四要素验证（姓名+身份证号+银行卡号+预留手机号）
- ✅ 活体检测（人脸识别）

### 核心功能

- **完整的认证流程管理**: 从提交到审核的全流程跟踪
- **多提供商支持**: 智能选择最佳认证服务提供商
- **人工审核机制**: 支持管理员后台手动通过/拒绝认证申请
- **EasyAdmin后台管理**: 完整的认证记录和提供商管理界面
- **审核统计分析**: 提供详细的审核数据统计和分析
- **RESTful API**: 标准化的API接口设计
- **安全性保障**: 敏感数据加密存储、访问频率限制

### 人工审核功能

- **一键审核**: 在后台管理界面直接通过或拒绝认证申请
- **拒绝原因管理**: 提供标准化拒绝原因选择和自定义原因输入
- **审核日志记录**: 完整记录审核人、审核时间和审核备注
- **批量审核**: 支持批量处理多个认证申请
- **审核统计**: 实时统计审核通过率、处理效率等指标
- **待审核提醒**: 显示待审核申请列表和等待时长

## 安装说明

### 依赖要求

#### 必需依赖

- **PHP**: >= 8.1
- **Symfony**: >= 6.4
  - `symfony/framework-bundle`
  - `symfony/security-bundle`
  - `symfony/validator`
- **Doctrine ORM**: >= 2.14
  - `doctrine/orm`
  - `doctrine/doctrine-bundle`
- **EasyAdmin**: >= 4.0
  - `easycorp/easyadmin-bundle`

#### 推荐依赖

- `symfony/monolog-bundle` - 用于日志记录
- `symfony/http-client` - 用于第三方API调用
- `nelmio/cors-bundle` - 用于跨域API访问

### 安装

将Bundle添加到你的Symfony项目中：

```bash
# 在项目根目录执行
composer require tourze/real-name-authentication-bundle
```

## 快速开始

### 配置

#### 1. 注册Bundle

在 `config/bundles.php` 中注册Bundle：

```php
return [
    // ...
    Tourze\RealNameAuthenticationBundle\RealNameAuthenticationBundle::class => ['all' => true],
];
```

#### 2. 数据库迁移

运行数据库迁移（如果有）：

```bash
php bin/console doctrine:migrations:migrate
```

#### 3. 基本配置

创建配置文件 `config/packages/real_name_authentication.yaml`：

```yaml
real_name_authentication:
    # 认证提供商配置
    providers:
        government:
            enabled: true
            priority: 100
        bank_union:
            enabled: true
            priority: 95
```

### 基本使用

最简单的认证流程示例：

```php
use Tourze\RealNameAuthenticationBundle\Service\PersonalAuthenticationService;
use Tourze\RealNameAuthenticationBundle\VO\PersonalAuthDTO;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

// 创建认证DTO
$authDTO = new PersonalAuthDTO();
$authDTO->setUserId('user123');
$authDTO->setMethod(AuthenticationMethod::ID_CARD_TWO_ELEMENTS);
$authDTO->setName('张三');
$authDTO->setIdCard('110101199003071234');

// 提交认证
$authService = $container->get(PersonalAuthenticationService::class);
$result = $authService->submitAuthentication($authDTO);

// 检查结果
if ($result->isSuccess()) {
    echo "认证提交成功，ID: " . $result->getAuthenticationId();
} else {
    echo "认证失败: " . $result->getErrorMessage();
}
```

## API使用示例

### 提交个人认证

```bash
curl -X POST http://your-domain/api/auth/personal/submit \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "user123",
    "method": "id_card_two_elements",
    "name": "张三",
    "idCard": "110101199003071234"
  }'
```

### 查询认证状态

```bash
curl http://your-domain/api/auth/personal/status/{authId}
```

### 查询认证历史

```bash
curl http://your-domain/api/auth/personal/history/{userId}
```

## 后台管理

访问EasyAdmin后台管理界面：

### 认证记录管理

- **查看认证记录**: 查看所有认证记录，支持筛选和详情查看
- **人工审核**: 对待审核的认证申请进行通过或拒绝操作
- **审核历史**: 查看认证记录的完整审核历史

### 认证提供商管理

- **提供商配置**: 配置第三方认证服务提供商
- **优先级管理**: 设置提供商的调用优先级
- **状态监控**: 监控提供商的可用状态

### 审核统计分析

- **实时统计**: 查看认证申请的实时统计数据
- **通过率分析**: 分析不同时间段的审核通过率
- **效率监控**: 监控审核处理效率和待审核队列

## 人工审核操作指南

### 审核认证申请

#### 1. 进入认证记录列表
- 访问后台管理 → 实名认证记录
- 筛选状态为"待审核"的记录

#### 2. 查看认证详情
- 点击"查看"按钮查看认证申请的详细信息
- 检查提交的认证数据是否完整准确

#### 3. 执行审核操作
- **通过认证**: 点击绿色"通过"按钮，系统将自动设置认证状态为已通过
- **拒绝认证**: 点击红色"拒绝"按钮，选择拒绝原因并填写备注

### 审核统计查看

#### 1. 访问统计页面
- 后台管理 → 认证审核统计
- 查看实时的审核数据统计

#### 2. 筛选统计数据
- 选择时间范围查看特定时期的统计
- 查看通过率、拒绝率等关键指标

#### 3. 处理待审核队列
- 查看最新的待审核申请
- 按等待时长优先处理长时间等待的申请

## 高级用法

### 自定义认证提供商

```php
use Tourze\RealNameAuthenticationBundle\Service\AuthenticationProviderService;

class CustomAuthenticationProvider implements AuthenticationProviderInterface
{
    public function authenticate(PersonalAuthDTO $dto): AuthenticationResult
    {
        // 实现自定义认证逻辑
        return new AuthenticationResult(/* ... */);
    }
}
```

### 扩展人工审核流程

```php
use Tourze\RealNameAuthenticationBundle\Service\ManualReviewService;

class CustomManualReviewService extends ManualReviewService
{
    public function approveAuthentication(int $authId, ?string $reviewComment = null): RealNameAuthentication
    {
        // 添加自定义审核逻辑
        $auth = parent::approveAuthentication($authId, $reviewComment);
        
        // 发送通知等后续处理
        $this->notificationService->sendApprovalNotification($auth);
        
        return $auth;
    }
}
```

### 批量导入认证数据

```php
use Tourze\RealNameAuthenticationBundle\Service\BatchImportService;

$importService = $container->get(BatchImportService::class);
$result = $importService->importFromFile('/path/to/auth-data.xlsx');
```

### 自定义认证规则

```php
// config/services.yaml
services:
    app.custom_auth_validator:
        class: App\Validator\CustomAuthValidator
        tags: ['real_name_auth.validator']
```

## 架构设计

### 核心组件

- **Entity层**: 标准化的实体设计，支持审计和时间戳
- **Repository层**: 丰富的查询方法，支持分页和筛选
- **Service层**: 完整的业务逻辑，包含验证、认证、提供商和人工审核服务
- **Controller层**: RESTful API和EasyAdmin后台管理

### 人工审核架构

- **ManualReviewService**: 人工审核核心服务，处理通过/拒绝逻辑
- **审核权限控制**: 基于Symfony Security组件的权限管理
- **审核日志系统**: 完整记录所有审核操作和变更历史
- **状态流转管理**: 确保认证状态的正确流转和一致性

### 安全特性

- 敏感数据加密存储
- 访问频率限制
- 输入数据验证和清理
- 完整的错误处理和日志记录
- 审核操作权限控制

## 技术规范

- 符合PSR-12代码规范
- 遵循Symfony最佳实践
- 支持PHP 8.1+
- 使用Doctrine ORM
- 集成EasyAdmin
- 支持Symfony 6.4+

## 开发状态

当前版本已完成：

- ✅ 核心Entity和枚举设计（支持PHP 8.1+特性）
- ✅ 完整的Service业务逻辑
- ✅ Repository查询接口
- ✅ 个人认证API控制器
- ✅ EasyAdmin后台管理界面
- ✅ 人工审核功能和流程
- ✅ 审核统计分析
- ✅ Bundle配置和服务注册
- ✅ 完整的单元测试和集成测试
- ✅ PHPStan level 6 代码质量检查
- ✅ Symfony 7.3 兼容性优化
- ✅ 批量导入功能
- ✅ 数据验证和安全性检查

近期更新：

- ✅ 修复TestUser实体的Symfony 7.3弃用警告
- ✅ 优化测试框架集成，使用AbstractIntegrationTestCase
- ✅ 完善异常处理和错误管理
- ✅ 提升代码质量和测试覆盖率
- ✅ 修复PHPStan错误，为测试类添加RunTestsInSeparateProcesses注解
- ✅ 优化测试隔离机制，确保测试之间无状态污染
- ✅ 完善服务层的审计日志记录和错误处理
- ✅ 修复 PersonalAuthenticationServiceTest 中的 5 个测试错误
- ✅ 创建模拟 HTTP 客户端避免测试环境中的真实网络请求
- ✅ 优化认证提供商测试配置，确保所有测试通过

待完成：

- ⭕ 认证结果管理界面优化
- ⭕ 数据加密存储增强
- ⭕ 高级统计报表和分析

## 许可证

MIT