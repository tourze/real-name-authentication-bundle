# Bundle独立性说明

## 📋 概述

实名认证Bundle已经完全独立，不依赖其他Bundle的asset或模板文件。

## 🔧 独立性特性

### 1. 模板独立

- ✅ 使用Bundle内部的 `@RealNameAuthentication/base.html.twig` 基础模板
- ✅ 所有前端模板都继承自Bundle内部模板
- ✅ 管理后台模板正确继承EasyAdmin布局
- ✅ 不依赖外部项目的 `base.html.twig`

### 2. 样式独立

- ✅ 使用CDN加载Bootstrap 5.3.0
- ✅ 使用CDN加载Font Awesome 6.0.0
- ✅ 内置完整的CSS样式定义
- ✅ 不依赖外部CSS文件

### 3. JavaScript独立

- ✅ 使用CDN加载Bootstrap JS
- ✅ 内置表单验证和交互逻辑
- ✅ 不依赖外部JavaScript库

### 4. 路由独立

- ✅ 使用Bundle内部的路由加载器
- ✅ 自动注册控制器路由
- ✅ 不依赖外部路由配置

## 🚀 使用方式

### 1. 安装Bundle

```bash
composer require tourze/real-name-authentication-bundle
```

### 2. 注册Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\RealNameAuthenticationBundle\RealNameAuthenticationBundle::class => ['all' => true],
];
```

### 3. 访问功能

- 前端认证界面：`/auth/personal/`
- API接口：`/api/auth/personal/`
- 管理后台：通过EasyAdmin访问

## 📁 模板结构

```
src/Resources/views/
├── base.html.twig                    # Bundle基础模板
├── admin/
│   ├── reject_form.html.twig        # 拒绝认证表单
│   └── statistics.html.twig         # 审核统计页面
└── personal_auth/
    ├── index.html.twig              # 认证方式选择
    ├── id_card_two.html.twig        # 身份证二要素认证
    ├── carrier_three.html.twig      # 运营商三要素认证
    ├── bank_card_three.html.twig    # 银行卡三要素认证
    ├── bank_card_four.html.twig     # 银行卡四要素认证
    ├── liveness.html.twig           # 活体检测认证
    ├── status.html.twig             # 认证状态查询
    └── history.html.twig            # 认证历史查询
```

## 🔒 安全特性

- 所有资源通过HTTPS CDN加载
- 内置XSS防护
- 表单CSRF保护
- 敏感数据脱敏显示

## 🎨 UI特性

- 响应式设计，支持移动端
- 现代化的Bootstrap 5界面
- 丰富的图标和视觉反馈
- 友好的用户体验

## 📝 注意事项

1. **CDN依赖**：Bundle使用CDN加载外部资源，确保网络连接正常
2. **浏览器兼容**：支持现代浏览器，建议使用Chrome、Firefox、Safari等
3. **JavaScript启用**：部分交互功能需要启用JavaScript

## 🔄 版本兼容

- PHP 8.1+
- Symfony 6.4+
- Bootstrap 5.3+
- Font Awesome 6.0+

---

**更新时间**: 2025-01-27  
**版本**: v1.2.0  
**状态**: ✅ 完全独立
 