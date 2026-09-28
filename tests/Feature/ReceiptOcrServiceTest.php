<?php

namespace Tests\Feature;

use App\Services\ReceiptOcrService;
use Tests\TestCase;

class ReceiptOcrServiceTest extends TestCase
{
    public function test_it_extracts_a_labelled_indonesian_receipt_total(): void
    {
        $result = app(ReceiptOcrService::class)->extract(<<<'TEXT'
WARUNG MAKAN SEJAHTERA
Jl. Sudirman No. 10
Tanggal: 25/09/2026
Nasi Goreng                 Rp 25.000
Es Teh                      Rp  7.500
TOTAL BAYAR                 Rp 32.500
Terima Kasih
TEXT);

        $this->assertSame('WARUNG MAKAN SEJAHTERA', $result['merchant']);
        $this->assertSame('2026-09-25', $result['meal_date']);
        $this->assertSame(32500.0, $result['amount']);
        $this->assertSame('Processed', $result['status']);
        $this->assertSame(0.95, $result['confidence']);
    }

    public function test_it_marks_receipts_without_a_labelled_total_for_review(): void
    {
        $result = app(ReceiptOcrService::class)->extract("KEDAI MAKAN\n26-09-2026\nRp 18.000");

        $this->assertSame('KEDAI MAKAN', $result['merchant']);
        $this->assertSame('2026-09-26', $result['meal_date']);
        $this->assertSame(18000.0, $result['amount']);
        $this->assertSame('Needs Review', $result['status']);
    }
}
