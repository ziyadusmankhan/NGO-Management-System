<?php

namespace App\Domain\Organization\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DirectoryExport
{
    public function pdf(array $report): string
    {
        $temp = storage_path('framework/cache/organization-pdf');
        if (! is_dir($temp) && ! mkdir($temp, 0755, true) && ! is_dir($temp)) {
            throw new \RuntimeException('PDF temporary storage is unavailable.');
        }
        $pdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $temp, 'default_font' => 'dejavusans', 'default_font_size' => 11,
            'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 35, 'margin_bottom' => 18, 'margin_header' => 9, 'margin_footer' => 8,
            'autoScriptToLang' => true, 'autoLangToFont' => true, 'shrink_tables_to_fit' => 1]);
        $pdf->SetTitle(config('organization.name').' - '.$report['title']);
        $pdf->SetAuthor(config('organization.name'));
        $pdf->SetCreator('Foundation Management System');
        $pdf->imageVars['foundation-logo'] = file_get_contents(config('organization.logo'));
        $pdf->SetHTMLHeader(view('organization.exports.header', compact('report'))->render());
        $pdf->SetHTMLFooter(view('organization.exports.footer', compact('report'))->render());
        $pdf->WriteHTML(view('organization.exports.pdf', compact('report'))->render());

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    public function xlsx(array $report): string
    {
        $book = IOFactory::load($report['template'] ?? config('organization.export_template'));
        $book->getProperties()->setCreator(config('organization.name'))->setTitle($report['title'])->setSubject('Organization register export');
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        foreach ($report['sections'] as $index => $section) {
            $sheet = $index === 0 ? $book->getSheet(0) : $book->createSheet();
            $sheet->setTitle($section['title']);
            $this->sheet($sheet, $report, $section, $index === 0);
        }
        $book->setActiveSheetIndex(0);
        ob_start();
        try {
            (new Xlsx($book))->save('php://output');

            return ob_get_contents();
        } finally {
            ob_end_clean();
            $book->disconnectWorksheets();
        }
    }

    private function text(Worksheet $sheet, string $cell, mixed $value): void
    {
        // Explicit strings retain leading zeroes and prevent spreadsheet formula injection.
        $sheet->setCellValueExplicit($cell, $value === null ? '' : (string) $value, DataType::TYPE_STRING);
    }

    private function sheet(Worksheet $sheet, array $report, array $section, bool $primary): void
    {
        $header = $primary ? 15 : 7;
        $first = $header + 1;
        $count = count($section['rows']);
        $last = max($first, $header + $count);
        $lastColumn = Coordinate::stringFromColumnIndex(count($section['headers']) + 1);
        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(80);
        $sheet->getColumnDimension('A')->setWidth(3);
        $sheet->getDefaultRowDimension()->setRowHeight(24);
        $sheet->setSelectedCell('B2');
        if ($primary) {
            $sheet->getCell('D2')->setValueExplicit(config('organization.name'), DataType::TYPE_STRING);
            $this->text($sheet, 'D3', $report['title']);
            $this->text($sheet, 'B10', $report['generated_at']->format('d M Y H:i').' PKT');
            $this->text($sheet, 'D10', $report['prepared_by']);
            $this->text($sheet, 'G10', $report['scope']);
            $this->text($sheet, 'B13', $section['title']);
            $this->text($sheet, 'B14', 'Current records matching the selected filters.');
            $sheet->getStyle('B10:I10')->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(10)->setRowHeight(32);
            if ($last < 115) {
                $sheet->removeRow($last + 1, 115 - $last);
            }
            $statusIndex = array_search('Status', $section['headers'], true);
            $statusColumn = Coordinate::stringFromColumnIndex($statusIndex + 2);
            $sheet->setCellValue('B6', '=COUNTA(B'.$first.':B'.$last.')');
            $sheet->setCellValue('D6', '=COUNTIFS('.$statusColumn.$first.':'.$statusColumn.$last.',"Active")');
            $sheet->setCellValue('F6', '=COUNTIFS('.$statusColumn.$first.':'.$statusColumn.$last.',"Inactive")');
            $sheet->setCellValue('H6', '=MAX(0,B6-D6-F6)');
            $sheet->getStyle('B6:I7')->getNumberFormat()->setFormatCode('#,##0');
            if (isset($report['metrics'])) {
                foreach (['B', 'D', 'F', 'H'] as $index => $column) {
                    $metric = $report['metrics'][$index];
                    $this->text($sheet, $column.'5', $metric['label']);
                    $sheet->getCell($column.'6')->setValueExplicit($metric['formula'] ?? $metric['value'], isset($metric['formula']) ? DataType::TYPE_FORMULA : DataType::TYPE_NUMERIC);
                    $sheet->getStyle($column.'6')->getNumberFormat()->setFormatCode($metric['type'] === 'money' ? '#,##0.00;[Red](#,##0.00)' : '#,##0');
                    $sheet->getStyle($column.'6')->getFont()->setSize($metric['type'] === 'money' ? 14 : 16);
                }
                $this->text($sheet, 'B14', $report['note']);
                $sheet->getStyle('B14:I14')->getAlignment()->setWrapText(true);
                $sheet->getRowDimension(14)->setRowHeight(48);
            }
        } else {
            $sheet->mergeCells('B2:'.$lastColumn.'2');
            $this->text($sheet, 'B2', config('organization.name'));
            $sheet->getStyle('B2:'.$lastColumn.'2')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('173F35');
            $sheet->mergeCells('B3:'.$lastColumn.'3');
            $this->text($sheet, 'B3', $section['title']);
            $sheet->mergeCells('B5:'.$lastColumn.'5');
            $this->text($sheet, 'B5', 'Generated '.$report['generated_at']->format('d M Y H:i').' PKT / '.$report['scope']);
            $sheet->getStyle('B5:'.$lastColumn.'5')->getFont()->getColor()->setRGB('586B62');
        }
        foreach ($section['headers'] as $index => $label) {
            $column = Coordinate::stringFromColumnIndex($index + 2);
            $this->text($sheet, $column.$header, $label);
            $sheet->getColumnDimension($column)->setWidth($section['widths'][$index]);
        }
        $sheet->getStyle('B'.$header.':'.$lastColumn.$header)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '173F35']],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($header)->setRowHeight(34);
        $sheet->getStyle('B'.$first.':'.$lastColumn.$last)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        foreach ($section['rows'] as $i => $values) {
            $row = $first + $i;
            $height = 30;
            foreach ($values as $j => $value) {
                $column = Coordinate::stringFromColumnIndex($j + 2);
                $cell = $column.$row;
                $type = $section['types'][$j];
                if ($value instanceof \DateTimeInterface) {
                    $sheet->setCellValueExplicit($cell, Date::dateTimeToExcel($value), DataType::TYPE_NUMERIC);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($type === 'datetime' ? 'dd mmm yyyy hh:mm' : 'dd mmm yyyy');
                } elseif (in_array($type, ['money', 'metric', 'quantity']) && $value !== null && is_numeric($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($type === 'quantity' ? '#,##0.000' : '#,##0.00;[Red](#,##0.00)');
                    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } elseif ($type === 'number' && $value !== null) {
                    $sheet->setCellValueExplicit($cell, (int) $value, DataType::TYPE_NUMERIC);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                } else {
                    $this->text($sheet, $cell, $value);
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('@');
                }
                if (is_string($value)) {
                    $lines = array_sum(array_map(fn ($line) => max(1, (int) ceil(mb_strlen($line) / max(10, $section['widths'][$j] - 3))), explode("\n", $value)));
                    $height = max($height, $lines * 14 + 12);
                }
            }
            $sheet->getRowDimension($row)->setRowHeight(min(409, $height));
            $sheet->getStyle('B'.$row.':'.$lastColumn.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($i % 2 ? 'F1F7F3' : 'FFFFFF');
            $sheet->getStyle('B'.$row.':'.$lastColumn.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('DEE6E1');
        }
        if (! $count) {
            $this->text($sheet, 'C'.$first, 'No matching records');
        }
        $tables = $sheet->getTableCollection();
        if ($tables->count()) {
            foreach ($tables as $table) {
                $table->setRange('B'.$header.':'.$lastColumn.$last);
                break;
            }
        } else {
            $sheet->addTable(new Table('B'.$header.':'.$lastColumn.$last, 'Export'.str_replace(' ', '', $section['title'])));
        }
        $sheet->freezePane('C'.$first);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($header, $header)->setPrintArea('B2:'.$lastColumn.$last);
        $sheet->getHeaderFooter()->setOddFooter('&L'.config('organization.name').'&RPage &P of &N');
        $sheet->getTabColor()->setRGB('173F35');
    }
}
