<div class="space-y-4">
    @forelse ($record->items as $item)
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                <div>
                    <strong>{{ $item->meal_date?->format('d M Y') }}</strong>
                    <span class="text-gray-500">&middot; {{ $item->meal_type }}{{ $item->merchant ? ' &middot; '.$item->merchant : '' }}</span>
                </div>
                <strong>Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</strong>
            </div>
            <div class="mb-3 text-xs text-gray-500">
                OCR: {{ $item->ocr_status }}@if ($item->ocr_confidence !== null) · {{ (int) round((float) $item->ocr_confidence * 100) }}% confidence @endif
            </div>
            <a href="{{ route('private.meal-claim-receipt', $item) }}" target="_blank" class="text-primary-600 underline dark:text-primary-400">
                Open receipt securely
            </a>
            @if ($item->note)
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $item->note }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500">No receipt items were found.</p>
    @endforelse
</div>
