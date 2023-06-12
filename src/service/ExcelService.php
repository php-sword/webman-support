<?php declare(strict_types=1);

namespace sword\service;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 封装文件导入导出服务
 * @see composer require phpoffice/phpspreadsheet 所需包安装
 * @version 1.0.1
 */
class ExcelService
{
    /**
     * @var array 表头
     */
    private array $colsIndex = [];

    /**
     * @var int 当前写入行号（光标）
     */
    private int $lineIndex = 1; //当前写入行光标

    /**
     * @var Spreadsheet 当前操作的表格
     */
    public Spreadsheet $spreadsheet;

    /**
     * @var Worksheet 当前操作的sheet
     */
    public Worksheet $sheet;

    /**
     * 创建或者打开一个表格
     * @param string|null $file 打开的文件
     * @param int $sheetIndex 打开的sheet
     * @throws Exception
     */
    public function __construct(?string $file = null, int $sheetIndex = 0)
    {
        if (is_null($file)) {
            $this->spreadsheet = new Spreadsheet();
        } else {
            $this->spreadsheet = IOFactory::load($file);
        }

        $this->sheet = $this->spreadsheet->getSheet($sheetIndex);
    }

    /**
     * 获取当前写入行号（光标）
     * @return int
     */
    public function getLineIndex(): int
    {
        return $this->lineIndex;
    }

    /**
     * 设置当前写入行号（光标）
     * @param int $lineIndex
     * @return static
     */
    public function setLineIndex(int $lineIndex): static
    {
        $this->lineIndex = $lineIndex;
        return $this;
    }

    /**
     * @param $sheetIndex
     * @return $this
     * @throws Exception
     */
    public function changeSheet($sheetIndex): static
    {
        $this->sheet = $this->spreadsheet->getSheet($sheetIndex);

        return $this;
    }

    /**
     * 设置表格列
     * @param array $cols ['列名' => '列宽']
     * @param int $line 行号，为0则以光标自动写入下一行
     * @return static
     */
    public function setCols(array $cols, int $line = 0): static
    {
        $this->colsIndex = $this->makeColumns(count($cols));

        if ($line == 0) {
            $line = $this->lineIndex + 1;
        }

        // 对单元格设置居中效果
        $index = 0;
        foreach ($cols as $colName => $colWidth) {
            $key = $this->colsIndex[$index++];

            $this->sheet->setCellValue($key . $line, $colName);
            $this->sheet->getColumnDimension($key)->setWidth($colWidth);
        }

        //设置当前行号
        $this->lineIndex = $line;
        return $this;
    }

    /**
     * 设置表格标题
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->sheet->setTitle($title);
        return $this;
    }

    /**
     * 写入一行数据
     * @param array $data ['值1',['值2', '类型', '居中方式', '字体颜色', '背景颜色', '字体大小']]
     * @param int $line 行号，为0则以光标自动写入下一行
     * @return $this
     */
    public function writeLine(array $data, int $line = 0): static
    {
        if ($line == 0) {
            $line = $this->lineIndex + 1;
        }
        foreach ($data as $key => $val) {
            $colKey = $this->colsIndex[$key];
            $coordinate = $colKey . $line;

            //如果是数组，则为单元格设置样式
            if (is_array($val)) {
                $this->sheet->setCellValueExplicit($coordinate, $val[0], $val[1] ?? 's');

                //获取单元格样式
                $style = $this->sheet->getStyle($coordinate);

                //水平居中方式 left right center general...
                if (isset($val[2]) and $val[2]) {
                    $style->getAlignment()->setHorizontal($val[2]);
                }

                //字体颜色
                if (isset($val[3]) and $val[3]) {
                    $style->getFont()->getColor()->setARGB($val[3]);
                }

                //背景颜色
                if (isset($val[4]) and $val[4]) {
                    $style->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($val[4]);
                }

                //字体大小
                if (isset($val[5]) and $val[5]) {
                    $style->getFont()->setSize($val[5]);
                }
            } else {
                $this->sheet->setCellValue($coordinate, $val);
            }
        }

        //设置当前行号
        $this->lineIndex = $line;
        return $this;
    }

    /**
     * 写入一个单元格数据
     */
    public function writeCell(string $col, string $row, string $val): static
    {
        $this->sheet->setCellValue($col . $row, $val);
        return $this;
    }

    /**
     * 保存数据到文件
     * @param resource|string $filename
     * @return void
     * @throws WriterException
     */
    public function save($filename): void
    {
        $xlsx = new Xlsx($this->spreadsheet);
        $xlsx->save($filename);
    }

    /**
     * 保存为xlsx文件并返回文件二进制数据
     * @return string
     * @throws WriterException
     */
    public function saveToXlsxBlob(): string
    {
        $stream = fopen('php://memory', 'w+');

        $xlsx = new Xlsx($this->spreadsheet);
        $xlsx->save($stream);
        rewind($stream);

        $read_data = '';
        while (!feof($stream)) {
            $read_data .= fgets($stream);
        }
        return $read_data;
    }

    /**
     * @param int $index max:256
     * @return string
     */
    private function getColumn(int $index): string
    {
        $a1 = floor($index / 26); //第一位的ASCII码序号
        $yu = $index % 26;
        $s = '';
        if ($yu == 0) {
            $yu = 26;
            $a1 = $a1 - 1;
        }
        if ($a1 > 0) {
            $s = chr(64 + $a1);
        }
        $s .= chr(64 + $yu);
        return $s;
    }

    /**
     * @param int $max
     * @return array
     */
    private function makeColumns(int $max): array
    {
        $cols = [];
        for ($i = 1; $i <= $max; $i++) {
            $cols[] = $this->getColumn($i);
        }
        return $cols;
    }

}