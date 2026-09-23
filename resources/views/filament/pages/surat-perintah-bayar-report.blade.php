<x-filament::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Report Filters</x-slot>
            <x-slot name="description">
                Use the billing period, company, or department to narrow down the report data.
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Applications</div>
                <div class="mt-2 text-2xl font-bold">Rp {{ number_format($stats['total'], 0, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-success-600">Approved</div>
                <div class="mt-2 text-2xl font-bold text-success-600">Rp {{ number_format($stats['approved'], 0, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-warning-600">Pending Approval</div>
                <div class="mt-2 text-2xl font-bold text-warning-600">Rp {{ number_format($stats['pending'], 0, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-danger-600">Rejected</div>
                <div class="mt-2 text-2xl font-bold text-danger-600">Rp {{ number_format($stats['rejected'], 0, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Documents</div>
                <div class="mt-2 text-2xl font-bold">{{ $stats['count'] }}</div>
            </x-filament::card>
        </div>

        <x-filament::section>
            <x-slot name="heading">Application Details</x-slot>
            <x-slot name="description">
                Use search, table filters, or Excel export for further processing.
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament::page>
