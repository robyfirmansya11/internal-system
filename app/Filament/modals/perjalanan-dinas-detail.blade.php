{{--
    resources/views/filament/modals/perjalanan-dinas-detail.blade.php
--}}

<div class="p-4 space-y-4 text-sm">

    {{-- ===== TOMBOL DOWNLOAD PDF ===== --}}
    <div class="flex justify-end mb-2">
        <a href="{{ $pdfUrl }}"
           target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white text-xs font-semibold
                  bg-green-600 hover:bg-green-700 transition-colors shadow">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Download PDF
        </a>
    </div>

    {{-- ===== HEADER ===== --}}
    <div class="text-center mb-2">
        <h2 class="text-base font-bold uppercase tracking-wider underline">
            Nota Penggantian Biaya Perjalanan
        </h2>
        <p class="text-xs tracking-widest text-gray-500">Travelling Expense Reimbursement Note</p>
    </div>

    {{-- ===== PT & TANGGAL ===== --}}
    <div class="flex justify-between items-start text-xs">
        <div>
            <span class="font-semibold">PT:</span>
            {{ $record->company?->nama ?? '-' }}
        </div>
        <div class="flex items-center gap-1">
            <span class="font-semibold">D</span>
            <span class="border border-gray-400 px-2 py-0.5 min-w-[28px] text-center">
                {{ $record->created_at?->format('d') }}
            </span>
            <span class="font-semibold">M</span>
            <span class="border border-gray-400 px-2 py-0.5 min-w-[28px] text-center">
                {{ $record->created_at?->format('m') }}
            </span>
            <span class="font-semibold">Y</span>
            <span class="border border-gray-400 px-2 py-0.5 min-w-[42px] text-center">
                {{ $record->created_at?->format('Y') }}
            </span>
        </div>
    </div>

    {{-- ===== INFO KARYAWAN ===== --}}
    <table class="w-full border border-gray-400 border-collapse text-xs">
        <tr>
            <td class="border border-gray-400 px-2 py-1 bg-gray-100 font-semibold w-1/5">
                Nama / <em>Name</em>
            </td>
            <td class="border border-gray-400 px-2 py-1 w-2/5">
                {{ $record->user?->name ?? '-' }}
            </td>
            <td class="border border-gray-400 px-2 py-1 bg-gray-100 font-semibold w-1/5">
                Keterangan / <em>Description</em>
            </td>
            <td class="border border-gray-400 px-2 py-1 w-1/5">
                {{ $record->keterangan ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="border border-gray-400 px-2 py-1 bg-gray-100 font-semibold">Department</td>
            <td class="border border-gray-400 px-2 py-1">{{ $record->department?->name ?? '-' }}</td>
            <td class="border border-gray-400 px-2 py-1 bg-gray-100 font-semibold">
                Jumlah Lampiran / <em>Total Attachment</em>
            </td>
            <td class="border border-gray-400 px-2 py-1">{{ $record->jumlah_lampiran ?? '-' }}</td>
        </tr>
    </table>

    {{-- ===== TABEL DETAIL PERJALANAN ===== --}}
    <div class="overflow-x-auto">
        <table class="w-full border border-gray-400 border-collapse" style="font-size: 10px; min-width: 900px;">
            <thead>
                <tr class="bg-gray-100 text-center">
                    <th colspan="4" class="border border-gray-400 px-1 py-1">
                        Waktu dan Tempat Keberangkatan /<br><em>Departure Time and Place</em>
                    </th>
                    <th colspan="4" class="border border-gray-400 px-1 py-1">
                        Waktu dan Tempat Tujuan /<br><em>Arrival Time and Place</em>
                    </th>
                    <th rowspan="2" class="border border-gray-400 px-1 py-1">
                        Jml<br>Hari /<br><em>Day(s)</em>
                    </th>
                    <th colspan="2" class="border border-gray-400 px-1 py-1">
                        Transportasi /<br><em>Transportation</em>
                    </th>
                    <th colspan="2" class="border border-gray-400 px-1 py-1">
                        Tunjangan /<br><em>Allowance</em>
                    </th>
                    <th colspan="3" class="border border-gray-400 px-1 py-1">Hotel</th>
                    <th colspan="2" class="border border-gray-400 px-1 py-1">
                        Lain-lain /<br><em>Other Expenses</em>
                    </th>
                    <th rowspan="2" class="border border-gray-400 px-1 py-1">Subtotal</th>
                </tr>
                <tr class="bg-gray-100 text-center" style="font-size: 9px;">
                    <th class="border border-gray-400 px-1 py-1">D</th>
                    <th class="border border-gray-400 px-1 py-1">M</th>
                    <th class="border border-gray-400 px-1 py-1">Y</th>
                    <th class="border border-gray-400 px-1 py-1">Tempat Berangkat</th>
                    <th class="border border-gray-400 px-1 py-1">D</th>
                    <th class="border border-gray-400 px-1 py-1">M</th>
                    <th class="border border-gray-400 px-1 py-1">Y</th>
                    <th class="border border-gray-400 px-1 py-1">Tempat Tujuan</th>
                    <th class="border border-gray-400 px-1 py-1">Jumlah</th>
                    <th class="border border-gray-400 px-1 py-1">Misc.</th>
                    <th class="border border-gray-400 px-1 py-1">Jumlah</th>
                    <th class="border border-gray-400 px-1 py-1">Misc.</th>
                    <th class="border border-gray-400 px-1 py-1">Hari</th>
                    <th class="border border-gray-400 px-1 py-1">Jumlah</th>
                    <th class="border border-gray-400 px-1 py-1">Misc.</th>
                    <th class="border border-gray-400 px-1 py-1">Ket.</th>
                    <th class="border border-gray-400 px-1 py-1">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($details as $detail)
                <tr class="text-center" style="font-size: 10px;">
                    <td class="border border-gray-400 px-1 py-1">
                        {{ \Carbon\Carbon::parse($detail->tanggal_berangkat)->format('d') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">
                        {{ \Carbon\Carbon::parse($detail->tanggal_berangkat)->format('m') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">
                        {{ \Carbon\Carbon::parse($detail->tanggal_berangkat)->format('Y') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1 text-left">
                        {{ $detail->tempat_berangkat ?? '-' }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">
                        {{ \Carbon\Carbon::parse($detail->tanggal_tujuan)->format('d') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">
                        {{ \Carbon\Carbon::parse($detail->tanggal_tujuan)->format('m') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">
                        {{ \Carbon\Carbon::parse($detail->tanggal_tujuan)->format('Y') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1 text-left">
                        {{ $detail->tempat_tujuan ?? '-' }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">{{ $detail->jumlah_hari ?? '-' }}</td>
                    <td class="border border-gray-400 px-1 py-1 text-right">
                        {{ number_format($detail->amount_transportasi ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">-</td>
                    <td class="border border-gray-400 px-1 py-1 text-right">
                        {{ number_format($detail->amount_tunjangan ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">-</td>
                    <td class="border border-gray-400 px-1 py-1">{{ $detail->lama_hotel ?? '-' }}</td>
                    <td class="border border-gray-400 px-1 py-1 text-right">
                        {{ number_format($detail->amount_hotel ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1">-</td>
                    <td class="border border-gray-400 px-1 py-1">{{ $detail->miso ?? '-' }}</td>
                    <td class="border border-gray-400 px-1 py-1 text-right">
                        {{ number_format($detail->amount_other ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="border border-gray-400 px-1 py-1 text-right font-semibold">
                        {{ number_format($detail->subtotal ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="19"
                        class="border border-gray-400 px-2 py-4 text-center text-gray-400 italic">
                        Tidak ada detail perjalanan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== TOTAL & TERBILANG ===== --}}
    <table class="w-full border border-gray-400 border-collapse text-xs">
        <tr>
            <td class="border border-gray-400 px-2 py-1 bg-gray-100 font-semibold w-1/4">
                Jumlah Total / <em>Total Amount</em>
            </td>
            <td class="border border-gray-400 px-2 py-1 font-bold text-right">
                IDR {{ number_format($record->total ?? 0, 0, ',', '.') }}
            </td>
        </tr>
        <tr>
            <td class="border border-gray-400 px-2 py-1 bg-gray-100 font-semibold">
                Terbilang / <em>In Words</em>
            </td>
            <td class="border border-gray-400 px-2 py-1 italic">
                {{ $record->terbilang ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="border border-gray-400 px-2 py-2 bg-gray-100 font-semibold align-top">
                Catatan / <em>Note</em><br>
                <span class="font-normal text-gray-500 text-xs">
                    (Informasi Transfer Dana / <em>Fund Transfer Information</em>)
                </span>
            </td>
            <td class="border border-gray-400 px-2 py-2">
                {{ $record->catatan ?? '-' }}
            </td>
        </tr>
    </table>

    {{-- ===== TANDA TANGAN ===== --}}
    <table class="w-full border border-gray-400 border-collapse text-xs mt-2">
        <tr>
            <td class="border border-gray-400 px-2 py-1 text-center w-1/5 align-top">
                <div class="text-gray-500 text-xs">Diajukan oleh / <em>Asked by</em></div>
                <div class="mt-10 border-t border-gray-400 pt-1 font-semibold">
                    {{ $record->user?->name ?? '___________' }}
                </div>
                <div class="text-xs text-gray-500">(Karyawan)</div>
            </td>
            <td class="border border-gray-400 px-2 py-1 text-center w-1/5 align-top">
                <div class="text-gray-500 text-xs">Diperiksa oleh / <em>Checked by</em></div>
                <div class="mt-10 border-t border-gray-400 pt-1 font-semibold">___________</div>
                <div class="text-xs text-gray-500">Dept. Manager</div>
            </td>
            <td class="border border-gray-400 px-2 py-1 text-center w-1/5 align-top">
                <div class="text-gray-500 text-xs">Disetujui oleh / <em>Approved by</em></div>
                <div class="mt-10 border-t border-gray-400 pt-1 font-semibold">___________</div>
                <div class="text-xs text-gray-500">Financial Manager</div>
            </td>
            <td class="border border-gray-400 px-2 py-1 text-center w-1/5 align-top">
                <div class="text-gray-500 text-xs">Disetujui oleh / <em>Approved by</em></div>
                <div class="mt-10 border-t border-gray-400 pt-1 font-semibold">___________</div>
                <div class="text-xs text-gray-500">Vice President</div>
            </td>
            <td class="border border-gray-400 px-2 py-1 text-center w-1/5 align-top">
                <div class="text-gray-500 text-xs">Disetujui oleh / <em>Approved by</em></div>
                <div class="mt-10 border-t border-gray-400 pt-1 font-semibold">
                    {{ $record->approvedBy?->name ?? '___________' }}
                </div>
                <div class="text-xs text-gray-500">President Director</div>
            </td>
        </tr>
    </table>

</div>
