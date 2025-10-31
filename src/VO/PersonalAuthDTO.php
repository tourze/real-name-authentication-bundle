<?php

namespace Tourze\RealNameAuthenticationBundle\VO;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;

/**
 * 个人认证数据传输对象
 */
class PersonalAuthDTO
{
    public function __construct(
        #[Assert\NotNull(message: '用户不能为空')]
        public readonly UserInterface $user,

        #[Assert\NotNull(message: '认证方式不能为空')]
        public readonly AuthenticationMethod $method,

        #[Assert\Length(min: 2, max: 50, minMessage: '姓名长度至少2个字符', maxMessage: '姓名长度不能超过50个字符')]
        public readonly ?string $name = null,

        #[Assert\Regex(
            pattern: '/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/',
            message: '身份证号码格式不正确'
        )]
        public readonly ?string $idCard = null,

        #[Assert\Regex(
            pattern: '/^1[3-9]\d{9}$/',
            message: '手机号码格式不正确'
        )]
        public readonly ?string $mobile = null,

        #[Assert\Length(min: 15, max: 19, minMessage: '银行卡号长度不正确', maxMessage: '银行卡号长度不正确')]
        public readonly ?string $bankCard = null,

        public readonly ?UploadedFile $image = null,
    ) {
    }

    /**
     * 转换为数组格式
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'user_identifier' => $this->user->getUserIdentifier(),
            'method' => $this->method->value,
        ];

        if (null !== $this->name) {
            $data['name'] = $this->name;
        }

        if (null !== $this->idCard) {
            $data['id_card'] = $this->idCard;
        }

        if (null !== $this->mobile) {
            $data['mobile'] = $this->mobile;
        }

        if (null !== $this->bankCard) {
            $data['bank_card'] = $this->bankCard;
        }

        return $data;
    }
}
