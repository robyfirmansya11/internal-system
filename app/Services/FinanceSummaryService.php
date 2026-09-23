<?php

namespace App\Services;

use App\Models\Kasbon;
use App\Models\NotaPenggantianBiaya;
use App\Models\PerjalananDinas;
use App\Models\SuratPerintahBayar;
use Illuminate\Support\Collection;

class FinanceSummaryService
{
    public function entries(array $filters = []): Collection
    {
        return collect()
            ->concat($this->map(SuratPerintahBayar::query(), 'Payment Application Letter', 'tanggal_penagihan', 'jumlah_total', $filters))
            ->concat($this->map(Kasbon::query(), 'Loan Note', 'tanggal', 'jumlah_dana', $filters))
            ->concat($this->map(PerjalananDinas::query(), 'Travel Reimbursement', 'created_at', 'total', $filters))
            ->concat($this->map(NotaPenggantianBiaya::query(), 'Expense Reimbursement Note', 'tanggal', 'jumlah_total', $filters))
            ->sortByDesc('date')
            ->values();
    }

    public function summary(array $filters = []): array
    {
        $entries = $this->entries($filters);

        return [
            'total' => $entries->sum('amount'),
            'approved' => $entries->where('status', 'Approved')->sum('amount'),
            'paid' => $entries->whereNotNull('paid_at')->sum('amount'),
            'outstanding' => $entries->filter(fn (array $entry): bool => ! in_array($entry['status'], ['Rejected', 'Cancelled'], true) && ! $entry['paid_at'])->sum('amount'),
            'count' => $entries->count(),
        ];
    }

    private function map($query, string $source, string $dateColumn, string $amountColumn, array $filters): Collection
    {
        return $query->with(['company', 'department', 'user'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate($dateColumn, '>=', $date))
            ->when($filters['date_until'] ?? null, fn ($q, $date) => $q->whereDate($dateColumn, '<=', $date))
            ->when($filters['company_id'] ?? null, fn ($q, $id) => $q->where('company_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->get()
            ->map(fn ($record): array => [
                'source' => $source,
                'reference' => $record->no_invoice ?? '#'.$record->id,
                'date' => $record->{$dateColumn},
                'company' => $record->company?->nama ?? '-',
                'department' => $record->department?->nama_department ?? ($record->user?->jabatan ?? '-'),
                'employee' => $record->user?->name ?? '-',
                'amount' => (float) ($record->{$amountColumn} ?? 0),
                'status' => $record->status,
                'paid_at' => $record->paid_at ?? null,
            ]);
    }
}
