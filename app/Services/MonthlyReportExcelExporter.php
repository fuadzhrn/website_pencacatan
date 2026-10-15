<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MonthlyReportExcelExporter
{
    /**
     * @param  array<string, mixed>  $report
     */
    public function make(array $report): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Bulanan');
        $spreadsheet->getProperties()
            ->setCreator('Sistem Informasi Preventive Maintenance X-Ray')
            ->setTitle('Laporan Preventive Maintenance Bulanan')
            ->setSubject($report['period']['label']);

        $this->writeHeading($sheet, $report);
        $lastRow = $this->writeMatrix($sheet, $report);
        $this->applyStyles($sheet, $lastRow);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $contents = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return $contents === false ? '' : $contents;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeHeading(Worksheet $sheet, array $report): void
    {
        $sheet->mergeCells('A1:AK1');
        $this->setText($sheet, 'A1', 'LAPORAN PREVENTIVE MAINTENANCE BULANAN');
        $sheet->mergeCells('A2:AK2');
        $this->setText($sheet, 'A2', 'Periode '.$report['period']['label']);

        $machine = $report['machine'];
        $this->setText($sheet, 'A4', 'Nama Mesin');
        $sheet->mergeCells('B4:E4');
        $this->setText($sheet, 'B4', $machine->equipment_name);
        $this->setText($sheet, 'F4', 'Merk');
        $sheet->mergeCells('G4:K4');
        $this->setText($sheet, 'G4', $machine->brand);
        $this->setText($sheet, 'A5', 'Tipe');
        $sheet->mergeCells('B5:C5');
        $this->setText($sheet, 'B5', $machine->type);
        $this->setText($sheet, 'D5', 'Serial Number');
        $this->setText($sheet, 'E5', $machine->serial_number);
        $this->setText($sheet, 'F5', 'Lokasi');
        $sheet->mergeCells('G5:K5');
        $this->setText($sheet, 'G5', $machine->location);
        $this->setText($sheet, 'A6', 'Bandara');
        $sheet->mergeCells('B6:E6');
        $this->setText($sheet, 'B6', $machine->airport);
        $this->setText($sheet, 'F6', 'Periode');
        $sheet->mergeCells('G6:K6');
        $this->setText($sheet, 'G6', $report['period']['label']);

        foreach (['A9' => 'No', 'B9' => 'Kategori', 'C9' => 'Frekuensi', 'D9' => 'Kegiatan', 'E9' => 'Kriteria'] as $cell => $label) {
            $this->setText($sheet, $cell, $label);
        }

        foreach (range(1, 31) as $day) {
            $sheet->setCellValue([5 + $day, 9], $day);
        }

        $this->setText($sheet, 'AK9', 'Keterangan');
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeMatrix(Worksheet $sheet, array $report): int
    {
        $rowNumber = 10;
        $sequence = 1;

        if ($report['groups'] === []) {
            $sheet->mergeCells('A10:AK10');
            $this->setText($sheet, 'A10', 'Tidak ada jadwal maintenance untuk mesin dan periode yang dipilih.');
            $sheet->getStyle('A10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            return 10;
        }

        foreach ($report['groups'] as $group) {
            foreach ($group['rows'] as $row) {
                $this->setText($sheet, 'A'.$rowNumber, (string) $sequence);
                $this->setText($sheet, 'B'.$rowNumber, $group['category']->name);
                $this->setText($sheet, 'C'.$rowNumber, $group['frequency_label']);
                $this->setText($sheet, 'D'.$rowNumber, $row['item']->item_name);
                $this->setText($sheet, 'E'.$rowNumber, $row['item']->criteria);

                foreach ($row['cells'] as $day => $cell) {
                    $coordinate = [5 + $day, $rowNumber];
                    $sheet->setCellValueExplicit($coordinate, $cell['symbol'], DataType::TYPE_STRING);
                    $this->styleStatusCell($sheet, $coordinate, $cell['state']);
                }

                $this->setText($sheet, 'AK'.$rowNumber, implode("\n", $row['notes']));
                $rowNumber++;
                $sequence++;
            }
        }

        return max(10, $rowNumber - 1);
    }

    private function applyStyles(Worksheet $sheet, int $lastRow): void
    {
        $sheet->getStyle('A1:AK'.$lastRow)->getFont()->setName('Arial')->setSize(8);
        $sheet->getStyle('A1:AK'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A1:AK'.$lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:AK'.$lastRow)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFE5E7EB');
        $sheet->getStyle('A1:AK2')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF111827');
        $sheet->getStyle('A1:AK2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A9:AK9')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A9:AK9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F2933');
        $sheet->getStyle('A9:AK'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D10:E'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('AK10:AK'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('E')->setWidth(36);

        foreach (range('F', 'Z') as $column) {
            $sheet->getColumnDimension($column)->setWidth(4);
        }

        foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(4);
        }

        $sheet->getColumnDimension('AK')->setWidth(55);
        $sheet->freezePane('F10');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A3)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd(9, 9);
        $sheet->getPageMargins()->setTop(0.3)->setRight(0.3)->setBottom(0.3)->setLeft(0.3);
        $sheet->getPageSetup()->setPrintArea('A1:AK'.$lastRow);
    }

    /**
     * @param  array{int, int}  $coordinate
     */
    private function styleStatusCell(Worksheet $sheet, array $coordinate, string $state): void
    {
        $colors = match ($state) {
            'conform' => ['FFD1FAE5', 'FF047857'],
            'nonconform' => ['FFFEE2E2', 'FFDC2626'],
            'pending' => ['FFFEF3C7', 'FF92400E'],
            default => ['FFF3F4F6', 'FF9CA3AF'],
        };
        $style = $sheet->getStyle($coordinate);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($colors[0]);
        $style->getFont()->setBold(true)->getColor()->setARGB($colors[1]);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function setText(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        $sheet->setCellValueExplicit($coordinate, (string) ($value ?? ''), DataType::TYPE_STRING);
    }
}
