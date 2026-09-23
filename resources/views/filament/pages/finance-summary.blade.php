<x-filament::page>
    @php($summary = $this->summary())
    <div class="space-y-6">
        <x-filament::section heading="Finance Summary Filters" description="Combined figures from payment applications, loan notes, travel reimbursements, and expense reimbursement notes.">
            {{ $this->form }}
        </x-filament::section>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach (['Total Requests' => ['total', 'gray'], 'Approved' => ['approved', 'success'], 'Paid (SPB)' => ['paid', 'primary'], 'Outstanding' => ['outstanding', 'warning']] as $label => [$key, $color])
                <x-filament::card><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-bold text-{{ $color }}-600">Rp {{ number_format($summary[$key], 0, ',', '.') }}</div></x-filament::card>
            @endforeach
            <x-filament::card><div class="text-sm text-gray-500">Documents</div><div class="mt-2 text-2xl font-bold">{{ $summary['count'] }}</div></x-filament::card>
        </div>
        <x-filament::section heading="Transaction Details" description="Scroll to review all transactions without extending the report page.">
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10" style="height: 36rem; overflow-y: scroll; scrollbar-gutter: stable;">
                <table class="min-w-[72rem] w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="sticky top-0 z-10 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 shadow-sm dark:bg-gray-900 dark:text-gray-300">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Source</th>
                            <th class="whitespace-nowrap px-4 py-3">Reference</th>
                            <th class="whitespace-nowrap px-4 py-3">Date</th>
                            <th class="whitespace-nowrap px-4 py-3">PT / Company</th>
                            <th class="whitespace-nowrap px-4 py-3">Department</th>
                            <th class="whitespace-nowrap px-4 py-3">Employee</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Amount</th>
                            <th class="whitespace-nowrap px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-white/5 dark:bg-gray-950">
                        @forelse ($this->entries() as $entry)
                            @php($statusColor = match ($entry['status']) { 'Approved' => 'success', 'Rejected', 'Cancelled' => 'danger', default => 'warning' })
                            <tr class="transition hover:bg-primary-50/60 dark:hover:bg-primary-500/10">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $entry['source'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $entry['reference'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ optional($entry['date'])->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $entry['company'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $entry['department'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $entry['employee'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">Rp {{ number_format($entry['amount'], 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center"><x-filament::badge :color="$statusColor">{{ $entry['status'] }}</x-filament::badge></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">No financial transactions found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>
