<?php

declare(strict_types=1);

namespace Tourze\RealNameAuthenticationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\RealNameAuthenticationBundle\Exception\InvalidAuthenticationDataException;
use Tourze\RealNameAuthenticationBundle\Service\CsvFileParser;

/**
 * @internal
 */
#[CoversClass(CsvFileParser::class)]
final class CsvFileParserTest extends TestCase
{
    private CsvFileParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvFileParser();
    }

    public function testParseValidCsvFile(): void
    {
        $csvContent = "姓名,身份证号,手机号\n张三,110101199001011234,13800138000\n李四,110101199002022345,13800138001";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(2, $result);
        $this->assertSame('张三', $result[0]['name']);
        $this->assertSame('110101199001011234', $result[0]['id_card']);
        $this->assertSame('13800138000', $result[0]['mobile']);
        $this->assertSame('李四', $result[1]['name']);
    }

    public function testParseWithFieldMapping(): void
    {
        $csvContent = "真实姓名,身份证,电话号码\n张三,110101199001011234,13800138000";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('id_card', $result[0]);
        $this->assertArrayHasKey('mobile', $result[0]);
        $this->assertSame('张三', $result[0]['name']);
    }

    public function testParseWithEmptyFile(): void
    {
        $csvContent = "姓名,身份证号\n";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(0, $result);
    }

    public function testParseWithMissingHeaders(): void
    {
        $csvContent = '';
        $file = $this->createCsvFile($csvContent);

        $this->expectException(InvalidAuthenticationDataException::class);
        $this->expectExceptionMessage('CSV文件格式错误:缺少头部行');

        $this->parser->parse($file);
    }

    public function testParseSkipsInvalidRows(): void
    {
        // 第二行列数不匹配,应该被跳过
        $csvContent = "姓名,身份证号,手机号\n张三,110101199001011234,13800138000\n李四,110101199002022345\n王五,110101199003033456,13800138002";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        // 只有第一行和第三行有效
        $this->assertCount(2, $result);
        $this->assertSame('张三', $result[0]['name']);
        $this->assertSame('王五', $result[1]['name']);
    }

    public function testParseHandlesNullValues(): void
    {
        $csvContent = "姓名,身份证号,手机号\n张三,,13800138000";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]['id_card']); // null转换为空字符串
    }

    public function testParseNormalizesFieldNames(): void
    {
        // 测试各种字段名变体
        $csvContent = "real_name,idcard,银行卡号\n张三,110101199001011234,6222021234567894";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('id_card', $result[0]);
        $this->assertArrayHasKey('bank_card', $result[0]);
    }

    public function testParseWithQuotedFields(): void
    {
        $csvContent = "姓名,身份证号,手机号\n\"张三\",\"110101199001011234\",\"13800138000\"";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(1, $result);
        $this->assertSame('张三', $result[0]['name']);
    }

    public function testParseWithCommasInQuotedFields(): void
    {
        $csvContent = "姓名,身份证号,备注\n\"张三\",\"110101199001011234\",\"测试,包含逗号\"";
        $file = $this->createCsvFile($csvContent);

        $result = $this->parser->parse($file);

        $this->assertCount(1, $result);
        // 备注字段会被标准化,但不会影响解析
        $this->assertSame('张三', $result[0]['name']);
    }

    private function createCsvFile(string $content): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        if (false === $tempFile) {
            throw new \RuntimeException('无法创建临时文件');
        }

        file_put_contents($tempFile, $content);

        return new UploadedFile(
            $tempFile,
            'test.csv',
            'text/csv',
            null,
            true // 测试模式
        );
    }
}
