<x-filament::page>

    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">

        <x-filament::card>
            <div class="text-sm text-gray-500">Total Amount</div>
            <div class="text-2xl font-bold">
                Rp {{ number_format($stats['total'], 0, ',', '.') }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-success-600">Approved</div>
            <div class="text-2xl font-bold text-success-600">
                Rp {{ number_format($stats['approved'], 0, ',', '.') }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-warning-600">Pending</div>
            <div class="text-2xl font-bold text-warning-600">
                Rp {{ number_format($stats['pending'], 0, ',', '.') }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-danger-600">Rejected</div>
            <div class="text-2xl font-bold text-danger-600">
                Rp {{ number_format($stats['rejected'], 0, ',', '.') }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-gray-500">Total Records</div>
            <div class="text-2xl font-bold">
                {{ $stats['count'] }}
            </div>
        </x-filament::card>

    </div>

    {{ $this->table }}

</x-filament::page>
