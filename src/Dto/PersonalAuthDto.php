<?php

namespace Tourze\RealNameAuthenticationBundle\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 个人认证数据传输对象
 */
class PersonalAuthDto
{
    #[Assert\NotBlank(message: '用户ID不能为空')]
    public readonly string $userId;

    #[Assert\NotNull(message: '认证方式不能为空')]
    public readonly AuthenticationMethod $method;

    #[Assert\Length(min: 2, max: 50, minMessage: '姓名长度至少2个字符', maxMessage: '姓名长度不能超过50个字符')]
    public readonly ?string $name;

    #[Assert\Regex(
        pattern: '/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/',
        message: '身份证号码格式不正确'
    )]
    public readonly ?string $idCard;

    #[Assert\Regex(
        pattern: '/^1[3-9]\d{9}$/',
        message: '手机号码格式不正确'
    )]
    public readonly ?string $mobile;

    #[Assert\Length(min: 15, max: 19, minMessage: '银行卡号长度不正确', maxMessage: '银行卡号长度不正确')]
    public readonly ?string $bankCard;

    public readonly ?UploadedFile $image;

    public function __construct(
        string $userId,
        AuthenticationMethod $method,
        ?string $name = null,
        ?string $idCard = null,
        ?string $mobile = null,
        ?string $bankCard = null,
        ?UploadedFile $image = null
    ) {
        $this->userId = $userId;
        $this->method = $method;
        $this->name = $name;
        $this->idCard = $idCard;
        $this->mobile = $mobile;
        $this->bankCard = $bankCard;
        $this->image = $image;
    }

    /**
     * 转换为数组格式
     */
    public function toArray(): array
    {
        $data = [
            'user_id' => $this->userId,
            'method' => $this->method->value,
        ];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->idCard !== null) {
            $data['id_card'] = $this->idCard;
        }

        if ($this->mobile !== null) {
            $data['mobile'] = $this->mobile;
        }

        if ($this->bankCard !== null) {
            $data['bank_card'] = $this->bankCard;
        }

        return $data;
    }
} 