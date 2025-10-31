<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\RealNameAuthenticationBundle\Exception\AuthenticationException;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;

/**
 * CSV文件解析器
 *
 * 负责解析CSV文件并标准化数据
 */
class CsvFileParser
{
    /**
     * 解析CSV文件
     *
     * @return array<int, array<string, string>>
     */
    public function parse(UploadedFile $file): array
    {
        $handle = $this->openFile($file);

        try {
            $headers = $this->readHeaders($handle);

            return $this->readDataRows($handle, $headers);
        } finally {
            fclose($handle);
        }
    }

    /**
     * 打开文件
     *
     * @return resource
     */
    private function openFile(UploadedFile $file)
    {
        $handle = fopen($file->getPathname(), 'r');

        if (false === $handle) {
            throw new AuthenticationException('无法打开CSV文件');
        }

        return $handle;
    }

    /**
     * 读取头部行
     *
     * @param resource $handle
     * @return array<int, string>
     */
    private function readHeaders($handle): array
    {
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (false === $headers) {
            throw new InvalidAuthenticationDataException('CSV文件格式错误:缺少头部行');
        }

        // 标准化头部字段名
        return array_map(fn (?string $field) => $this->normalizeFieldName($field ?? ''), $headers);
    }

    /**
     * 读取数据行
     *
     * @param resource $handle
     * @param array<int, string> $headers
     * @return array<int, array<string, string>>
     */
    private function readDataRows($handle, array $headers): array
    {
        $data = [];
        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $processedRow = $this->processRow($row, $headers);
            if (null !== $processedRow) {
                $data[$rowNumber] = $processedRow;
                ++$rowNumber;
            }
        }

        return $data;
    }

    /**
     * 处理单行数据
     *
     * @param array<int, string|null> $row
     * @param array<int, string> $headers
     * @return array<string, string>|null
     */
    private function processRow(array $row, array $headers): ?array
    {
        if (count($row) !== count($headers)) {
            return null; // 跳过格式错误的行
        }

        // array_combine可能返回 false,尽管 PHPDoc 显示为 non-empty-array
        $combinedRow = array_combine($headers, $row);
        // @phpstan-ignore-next-line
        if (false === $combinedRow) {
            return null; // 跳过无法组合的行
        }

        // 确保所有值都是字符串类型
        return array_map(fn ($value) => $value ?? '', $combinedRow);
    }

    /**
     * 标准化字段名
     */
    private function normalizeFieldName(string $fieldName): string
    {
        $fieldMap = $this->getFieldNameMap();
        $normalizedName = trim($fieldName);

        return $fieldMap[$normalizedName] ?? strtolower(str_replace([' ', '-', '_'], '', $normalizedName));
    }

    /**
     * 获取字段名映射
     *
     * @return array<string, string>
     */
    private function getFieldNameMap(): array
    {
        return [
            '姓名' => 'name',
            '真实姓名' => 'name',
            'real_name' => 'name',
            'realname' => 'name',
            '身份证号' => 'id_card',
            '身份证' => 'id_card',
            'id_card' => 'id_card',
            'idcard' => 'id_card',
            '手机号' => 'mobile',
            '手机号码' => 'mobile',
            '电话号码' => 'mobile',
            '银行卡号' => 'bank_card',
            '银行卡' => 'bank_card',
            '认证方式' => 'method',
            '认证类型' => 'method',
        ];
    }
}
