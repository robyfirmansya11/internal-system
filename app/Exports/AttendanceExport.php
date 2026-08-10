<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected int $month;

    protected int $year;

    protected ?int $userId;

    protected ?int $departmentId;

    protected array $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(
        int $month,
        int $year,
        ?int $userId = null,
        ?int $departmentId = null,
    ) {
        $this->month = $month;
        $this->year = $year;
        $this->userId = $userId;
        $this->departmentId = $departmentId;
    }

    public function collection()
    {
        return Attendance::with(['user.departments'])
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->departmentId, function ($q) {
                $q->whereHas('user.departments', function ($q2) {
                    $q2->where('departments.id', $this->departmentId);
                });
            })
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            // Baris 1: judul besar
            ['REKAP ABSENSI KARYAWAN', '', '', '', '', '', '', '', '', ''],
            // Baris 2: periode
            ['Periode: '.$this->monthNames[$this->month].' '.$this->year, '', '', '', '', '', '', '', '', ''],
            // Baris 3: tanggal export
            ['Diekspor: '.now()->translatedFormat('d F Y, H:i').' WIB', '', '', '', '', '', '', '', '', ''],
            // Baris 4: kosong
            [],
            // Baris 5: header kolom
            [
                'No',
                'Nama Karyawan',
                'Department',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Catatan',
                'Alasan Terlambat',
                'Alasan Pulang Awal',
            ],
        ];
    }

    protected int $rowNumber = 0;

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->user?->name ?? '-',
            $row->user?->departments->first()?->nama_department ?? '-',
            $row->date?->format('d/m/Y'),
            $row->clock_in?->format('H:i') ?? '-',
            $row->clock_out?->format('H:i') ?? '-',
            match ($row->status) {
                'present' => 'Hadir',
                'late' => 'Terlambat',
                'absent' => 'Tidak Hadir',
                default => $row->status ?? '-',
            },
            $row->note ?? '-',
            $row->clock_in_reason ?? '-',
            $row->clock_out_reason ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Baris 1 — judul besar
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1B4F8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Baris 2 & 3 — info periode
            2 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['size' => 10, 'color' => ['rgb' => '6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Baris 5 — header kolom
            5 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1B4F8A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowNumber + 5;
                $lastCol = 'J';

                // Merge judul (baris 1-3)
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->mergeCells("A3:{$lastCol}3");

                // Border untuk data (mulai baris 5)
                $sheet->getStyle("A5:{$lastCol}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Warna alternating rows untuk data
                for ($i = 6; $i <= $lastRow; $i++) {
                    if ($i % 2 === 0) {
                        $sheet->getStyle("A{$i}:{$lastCol}{$i}")
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('F0F4FF');
                    }
                }

                // Warna status
                for ($i = 6; $i <= $lastRow; $i++) {
                    $status = $sheet->getCell("G{$i}")->getValue();

                    $color = match ($status) {
                        'Hadir' => '22C55E',
                        'Terlambat' => 'F59E0B',
                        'Tidak Hadir' => 'EF4444',
                        default => null,
                    };

                    if ($color) {
                        $sheet->getStyle("G{$i}")
                            ->getFont()
                            ->getColor()
                            ->setRGB($color);

                        $sheet->getStyle("G{$i}")
                            ->getFont()
                            ->setBold(true);
                    }
                }

                // Center alignment untuk kolom tertentu
                $sheet->getStyle("A6:A{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("D6:G{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Freeze pane di baris 6 (header tetap terlihat saat scroll)
                $sheet->freezePane('A6');

                // Row height untuk header
                $sheet->getRowDimension(5)->setRowHeight(25);
            },
        ];
    }

    public function title(): string
    {
        return 'Rekap '.$this->monthNames[$this->month].' '.$this->year;
    }
}
