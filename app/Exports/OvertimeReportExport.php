<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OvertimeReportExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithEvents, WithTitle
{
    private const LAST_COLUMN = 'H';

    private const DATA_START_ROW = 9;

    public function __construct(
        private readonly Collection $records,
        private readonly ?string $employee,
        private readonly ?string $department,
        private readonly string $period,
    ) {}

    public function array(): array
    {
        $rows = [
            ['FM/HRD/011.00'],
            ['LAPORAN LEMBUR / OVERTIME REPORT'],
            $this->blankRow(),
            ['Nama', $this->employee ?? '', '', '', 'Periode / Period', $this->period],
            ['Department', $this->department ?? ''],
            $this->blankRow(),
            ['Tanggal / Date', 'Jam Kerja / Working Hours', '', 'Lembur / Overtime', '', 'Uang Makan / Meal Allow.', 'Uraian Pekerjaan / Job Description', 'Jumlah Jam Lembur / Total OT Hours'],
            ['', 'Mulai / Start', 'Selesai / End', 'Mulai / Start', 'Selesai / End', '', '', ''],
        ];

        $dataRows = $this->records->map(fn ($record) => [
            $record->tanggal_lembur ? Date::dateTimeToExcel($record->tanggal_lembur) : null,
            $this->timeToExcel($record->mulai_kerja),
            $this->timeToExcel($record->selesai_kerja),
            $this->timeToExcel($record->mulai_lembur),
            $this->timeToExcel($record->selesai_lembur),
            (float) ($record->uang_makan ?? 0),
            $record->uraian_pekerjaan ?? '',
            (float) ($record->jumlah_jam_lembur ?? 0),
        ])->all();

        // PDF menyediakan baris kosong saat belum ada data. Pertahankan perilaku itu di Excel.
        if ($dataRows === []) {
            $dataRows = array_fill(0, 26, array_fill(0, 8, null));
        }

        $rows = [...$rows, ...$dataRows];
        $totalRow = self::DATA_START_ROW + count($dataRows);

        $rows[] = [
            'Total Jam Lembur / Total Overtime Hours', '', '', '', '', '', '',
            '=SUM(H'.self::DATA_START_ROW.':H'.($totalRow - 1).')',
        ];
        $rows[] = $this->blankRow();
        $rows[] = ['Dibuat Oleh / Prepared by', '', '', 'Disetujui Oleh / Approved by', '', '', 'Mengetahui / Acknowledged by', ''];
        $rows[] = $this->blankRow();
        $rows[] = $this->blankRow();
        $rows[] = ['( '.($this->employee ?? '____________________').' )', '', '', '( ______________________ )', '', '', '( ______________________ )', ''];
        $rows[] = ['Karyawan / Employee', '', '', 'Atasan / Manager', '', '', 'HRD / HR Department', ''];
        $rows[] = $this->blankRow();
        $rows[] = ['Generated on '.now()->translatedFormat('d F Y H:i').' WIB'];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 12,
            'C' => 12,
            'D' => 12,
            'E' => 12,
            'F' => 17,
            'G' => 42,
            'H' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $dataCount = max($this->records->count(), 26);
                $dataEndRow = self::DATA_START_ROW + $dataCount - 1;
                $totalRow = $dataEndRow + 1;
                $signatureRow = $totalRow + 3;
                $footerRow = $signatureRow + 6;

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('B4:D4');
                $sheet->mergeCells('F4:H4');
                $sheet->mergeCells('B5:H5');
                $sheet->mergeCells('A7:A8');
                $sheet->mergeCells('B7:C7');
                $sheet->mergeCells('D7:E7');
                $sheet->mergeCells('F7:F8');
                $sheet->mergeCells('G7:G8');
                $sheet->mergeCells('H7:H8');
                $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
                $sheet->mergeCells("A{$signatureRow}:C{$signatureRow}");
                $sheet->mergeCells("D{$signatureRow}:F{$signatureRow}");
                $sheet->mergeCells("G{$signatureRow}:H{$signatureRow}");
                $sheet->mergeCells('A'.($signatureRow + 1).':C'.($signatureRow + 2));
                $sheet->mergeCells('D'.($signatureRow + 1).':F'.($signatureRow + 2));
                $sheet->mergeCells('G'.($signatureRow + 1).':H'.($signatureRow + 2));
                $sheet->mergeCells('A'.($signatureRow + 3).':C'.($signatureRow + 3));
                $sheet->mergeCells('D'.($signatureRow + 3).':F'.($signatureRow + 3));
                $sheet->mergeCells('G'.($signatureRow + 3).':H'.($signatureRow + 3));
                $sheet->mergeCells('A'.($signatureRow + 4).':C'.($signatureRow + 4));
                $sheet->mergeCells('D'.($signatureRow + 4).':F'.($signatureRow + 4));
                $sheet->mergeCells('G'.($signatureRow + 4).':H'.($signatureRow + 4));
                $sheet->mergeCells("A{$footerRow}:H{$footerRow}");

                $sheet->getStyle('A1:H2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1E3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A1')->getFont()->setSize(12);
                $sheet->getStyle('A2')->getFont()->setSize(14);
                $sheet->getStyle('A4:H5')->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '333333']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('A4:A5')->getFont()->setBold(true);
                $sheet->getStyle('E4')->getFont()->setBold(true);

                $sheet->getStyle('A7:H8')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1E3A5F'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F3F7']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getStyle("A7:H{$totalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('999999'));
                $sheet->getStyle('A'.self::DATA_START_ROW.":F{$dataEndRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G'.self::DATA_START_ROW.":G{$dataEndRow}")
                    ->getAlignment()
                    ->setWrapText(true);
                $sheet->getStyle('H'.self::DATA_START_ROW.":H{$dataEndRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A'.self::DATA_START_ROW.":A{$dataEndRow}")
                    ->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('B'.self::DATA_START_ROW.":E{$dataEndRow}")
                    ->getNumberFormat()
                    ->setFormatCode('hh:mm');
                $sheet->getStyle('F'.self::DATA_START_ROW.":F{$dataEndRow}")
                    ->getNumberFormat()
                    ->setFormatCode('[$Rp-421] #,##0');
                $sheet->getStyle('H'.self::DATA_START_ROW.":H{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('0.00');
                $sheet->getStyle("A{$totalRow}:H{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1E3A5F']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("A{$signatureRow}:H".($signatureRow + 4))->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("A{$signatureRow}:H{$signatureRow}")->getFont()->setBold(true);
                $sheet->getStyle('A'.($signatureRow + 1).':H'.($signatureRow + 2))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('999999'));
                $sheet->getStyle('A'.($signatureRow + 3).':H'.($signatureRow + 4))
                    ->getFont()
                    ->setBold(true);
                $sheet->getStyle("A{$footerRow}")->applyFromArray([
                    'font' => ['size' => 8, 'color' => ['rgb' => '999999']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(7)->setRowHeight(32);
                $sheet->getRowDimension(8)->setRowHeight(30);
                for ($row = self::DATA_START_ROW; $row <= $dataEndRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }
                $sheet->getRowDimension($signatureRow + 1)->setRowHeight(30);
                $sheet->getRowDimension($signatureRow + 2)->setRowHeight(30);
                $sheet->freezePane('A9');
                $sheet->getPageSetup()->setOrientation('landscape');
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.3);
                $sheet->getPageMargins()->setBottom(0.3);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setRight(0.25);
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Lembur';
    }

    private function timeToExcel(mixed $time): ?float
    {
        if (! $time) {
            return null;
        }

        $time = Carbon::parse($time);

        return ($time->hour * 3600 + $time->minute * 60 + $time->second) / 86400;
    }

    private function blankRow(): array
    {
        // Laravel Excel menghapus array kosong. Satu string kosong mempertahankan nomor barisnya.
        return [''];
    }
}
