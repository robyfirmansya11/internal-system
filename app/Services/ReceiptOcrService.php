<?php

namespace App\Services;

use App\Models\MealClaim;
use App\Models\MealClaimItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class ReceiptOcrService
{
    public function isAvailable(): bool
    {
        if (! config('receipt_ocr.enabled')) {
            return false;
        }

        try {
            $process = new Process([config('receipt_ocr.binary'), '--version']);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Read a private receipt image and populate only fields that remain empty.
     * The user or Claim Admin can correct every extracted value afterwards.
     *
     * @return array{status: string, confidence: float, message: string}
     */
    public function process(MealClaimItem $item, bool $overwrite = false): array
    {
        if (! $this->isAvailable()) {
            return $this->markFailed($item, 'OCR is not configured or Tesseract is unavailable.');
        }

        if (! Storage::disk('private')->exists($item->receipt_path)) {
            return $this->markFailed($item, 'Receipt image was not found in private storage.');
        }

        try {
            $process = new Process([
                config('receipt_ocr.binary'),
                Storage::disk('private')->path($item->receipt_path),
                'stdout',
                '-l', config('receipt_ocr.languages'),
                '--psm', (string) config('receipt_ocr.page_segmentation_mode'),
            ]);
            $process->setTimeout(config('receipt_ocr.timeout'));
            $process->run();

            if (! $process->isSuccessful()) {
                return $this->markFailed($item, 'Tesseract could not read this receipt image.');
            }

            $result = $this->extract($process->getOutput());
            $values = [
                'ocr_status' => $result['status'],
                'ocr_confidence' => $result['confidence'],
                'ocr_raw_text' => $result['raw_text'],
            ];

            if (($overwrite || blank($item->merchant)) && $result['merchant']) {
                $values['merchant'] = $result['merchant'];
            }

            if (($overwrite || ! $item->meal_date) && $result['meal_date']) {
                $values['meal_date'] = $result['meal_date'];
            }

            if (($overwrite || (float) $item->amount <= 0) && $result['amount'] !== null) {
                $values['amount'] = $result['amount'];
            }

            $item->update($values);

            return [
                'status' => $result['status'],
                'confidence' => $result['confidence'],
                'message' => $result['status'] === 'Processed'
                    ? 'Receipt data was extracted.'
                    : 'Receipt text was found, but needs manual review.',
            ];
        } catch (Throwable) {
            return $this->markFailed($item, 'The OCR process failed unexpectedly.');
        }
    }

    /** @return array{processed: int, needs_review: int, failed: int} */
    public function processClaim(MealClaim $claim, bool $overwrite = false): array
    {
        $summary = ['processed' => 0, 'needs_review' => 0, 'failed' => 0];

        foreach ($claim->items as $item) {
            $result = $this->process($item, $overwrite);

            match ($result['status']) {
                'Processed' => $summary['processed']++,
                'Needs Review' => $summary['needs_review']++,
                default => $summary['failed']++,
            };
        }

        return $summary;
    }

    /**
     * Extract structured candidates from raw receipt text. The returned total
     * remains a candidate: a person must validate it against the receipt.
     *
     * @return array{merchant: ?string, meal_date: ?string, amount: ?float, confidence: float, status: string, raw_text: string}
     */
    public function extract(string $rawText): array
    {
        $lines = collect(preg_split('/\R/u', $rawText) ?: [])
            ->map(fn (string $line): string => trim(preg_replace('/\s+/u', ' ', $line) ?? ''))
            ->filter()
            ->values();

        $merchant = $this->findMerchant($lines->all());
        $mealDate = $this->findDate($lines->all());
        [$amount, $isLabelledTotal] = $this->findAmount($lines->all());

        $confidence = 0.0;
        $confidence += $merchant ? 0.15 : 0;
        $confidence += $mealDate ? 0.20 : 0;
        $confidence += $amount !== null ? ($isLabelledTotal ? 0.60 : 0.30) : 0;
        $confidence = round(min($confidence, 0.95), 2);

        return [
            'merchant' => $merchant,
            'meal_date' => $mealDate,
            'amount' => $amount,
            'confidence' => $confidence,
            'status' => $confidence >= 0.70 ? 'Processed' : 'Needs Review',
            'raw_text' => trim($rawText),
        ];
    }

    /** @param array<int, string> $lines */
    private function findMerchant(array $lines): ?string
    {
        foreach (array_slice($lines, 0, 8) as $line) {
            $normalized = mb_strtoupper($line);

            if (mb_strlen($line) < 3 || mb_strlen($line) > 100
                || preg_match('/^[-\d\s.,\/:]+$/u', $line)
                || preg_match('/(STRUK|RECEIPT|INVOICE|TOTAL|SUBTOTAL|TERIMA KASIH|CASHIER|TABLE|MEJA|ORDER|TANGGAL)/u', $normalized)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    /** @param array<int, string> $lines */
    private function findDate(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (! preg_match('/\b(\d{1,2}[\/.\-]\d{1,2}[\/.\-]\d{2,4})\b/', $line, $matches)) {
                continue;
            }

            foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y', 'd.m.y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $matches[1])->startOfDay()->toDateString();
                } catch (Throwable) {
                    // Try the next common receipt format.
                }
            }
        }

        return null;
    }

    /** @param array<int, string> $lines @return array{?float, bool} */
    private function findAmount(array $lines): array
    {
        foreach (array_reverse($lines) as $line) {
            $upper = mb_strtoupper($line);

            if (str_contains($upper, 'SUBTOTAL') || ! preg_match('/(GRAND\s*TOTAL|TOTAL\s*BAYAR|TOTAL|JUMLAH\s*BAYAR|NETT|TOTAL\s*TAGIHAN)/u', $upper)) {
                continue;
            }

            $amount = $this->lastAmountOnLine($line);
            if ($amount !== null) {
                return [$amount, true];
            }
        }

        $amounts = collect($lines)
            ->map(fn (string $line) => $this->lastAmountOnLine($line))
            ->filter(fn (?float $amount) => $amount !== null)
            ->values();

        return [$amounts->isNotEmpty() ? (float) $amounts->max() : null, false];
    }

    private function lastAmountOnLine(string $line): ?float
    {
        preg_match_all('/(?:RP\.?\s*)?\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{1,2})?|(?:RP\.?\s*)?\d+/iu', $line, $matches);
        $candidate = collect($matches[0] ?? [])
            ->map(fn (string $amount): ?float => $this->normaliseAmount($amount))
            ->filter(fn (?float $amount) => $amount !== null && $amount > 0)
            ->last();

        return $candidate === null ? null : (float) $candidate;
    }

    private function normaliseAmount(string $value): ?float
    {
        $value = preg_replace('/[^\d.,]/u', '', $value) ?? '';

        if ($value === '') {
            return null;
        }

        $comma = strrpos($value, ',');
        $dot = strrpos($value, '.');

        if ($comma !== false && $dot !== false) {
            $value = $comma > $dot
                ? str_replace(',', '.', str_replace('.', '', $value))
                : str_replace(',', '', $value);
        } elseif ($comma !== false) {
            $after = strlen($value) - $comma - 1;
            $value = $after === 3 ? str_replace(',', '', $value) : str_replace(',', '.', $value);
        } elseif ($dot !== false) {
            $after = strlen($value) - $dot - 1;
            $value = $after === 3 ? str_replace('.', '', $value) : $value;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /** @return array{status: string, confidence: float, message: string} */
    private function markFailed(MealClaimItem $item, string $message): array
    {
        $item->update(['ocr_status' => 'Failed', 'ocr_confidence' => 0, 'ocr_raw_text' => null]);

        return ['status' => 'Failed', 'confidence' => 0, 'message' => $message];
    }
}
