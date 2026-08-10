{{-- resources/views/pdf/perjalanan-dinas.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Penggantian Biaya Perjalanan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 11px;
            font-weight: normal;
            letter-spacing: 1px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .meta-row .pt {
            font-size: 10px;
        }
        .meta-row .tanggal {
            font-size: 10px;
            display: flex;
            gap: 12px;
        }
        .tanggal-box {
            border: 1px solid #000;
            padding: 2px 8px;
            min-width: 30px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
table th,
table td {
    border: 1px solid #000;
    padding: 4px 5px;
    text-align: center;
    font-size: 9px;
    vertical-align: middle;
    line-height: 1.2;
}
tbody tr {
    height: 24px;
}

        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .section-label {
            font-weight: bold;
            padding: 4px 6px;
            background: #f5f5f5;
            border: 1px solid #000;
        }
        .info-row td {
            padding: 4px 6px;
        }
        .summary-table td {
            padding: 5px 8px;
        }
        .signature-section {
            margin-top: 20px;
        }
.signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0px;
}

.signature-table td {
    border: 1px solid #000;
    text-align: center;
    padding: 5px;
    width: 20%;
    vertical-align: top;
}

.signature-space {
    height: 50px;
    position: relative;
}

.signature-name {
    font-size: 10px;
    border-top: 1px solid #000;
    padding-top: 3px;
    margin-top: 3px;
}
        .signature-name {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: bold;
        }
        .signature-title {
            font-size: 9px;
            margin-top: 2px;
        }
        .label-sm {
            font-size: 8px;
            color: #555;
        }
        .document {
    background: #ffffff;
    color: #000000;
    padding: 20px;
    max-width: 1200px;
    margin: auto;
}
thead th {
    background: #e6e6e6;
    font-weight: bold;
}

table td {
    height: 18px;
}

.signature-space {
    height: 10px;
    position: relative;
}

.approval-stamp {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-15deg);
    border: 3px solid;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 2px;
    opacity: 0.6;
    white-space: nowrap;
}
    </style>
</head>
<body>

    <div class="document">

    {{-- HEADER --}}
    <div class="header">
        <h1>Nota Penggantian Biaya Perjalanan</h1>
        <h2>Travelling Expense Reimbursement Note</h2>
    </div>

    {{-- META: PT & TANGGAL --}}
    <table style="border: none; margin-bottom: 6px;">
        <tr>
            <td style="border: none; width: 60%; font-size: 10px; text-align: left;">
                <strong>PT:</strong> {{ $record->company?->kode ?? '-' }}
            </td>
            <td style="border: none; text-align: right; font-size: 10px;">
                D <span class="tanggal-box">{{ $record->created_at?->format('d') }}</span>
                &nbsp;M <span class="tanggal-box">{{ $record->created_at?->format('m') }}</span>
                &nbsp;Y <span class="tanggal-box">{{ $record->created_at?->format('Y') }}</span>
            </td>
        </tr>
    </table>

    {{-- INFO KARYAWAN --}}
    <table style="margin-bottom: 6px;">
        <tr>
            <td style="width: 30%; text-align: left; background: #f5f5f5; font-weight: bold;">Nama / Name</td>
            <td style="text-align: left;">{{ $record->user?->name ?? '-' }}</td>
            <td style="width: 15%; background: #f5f5f5; font-weight: bold;">Keterangan / Description</td>
            <td style="width: 15%; text-align: left;">{{ $record->keterangan ?? '-' }}</td>
        </tr>
        <tr>
            <td style="background: #f5f5f5; font-weight: bold; text-align: left;">Dept.</td>
            <td style="text-align: left;">{{ $record->department?->name ?? '-' }}</td>
            <td style="background: #f5f5f5; font-weight: bold;">Jumlah Lampiran / Total Attachment</td>
            <td style="text-align: left;">{{ $record->jumlah_lampiran ?? '-' }}</td>
        </tr>
    </table>

    {{-- TABEL DETAIL PERJALANAN --}}
    <table>
<thead>
<tr>
    <th colspan="4">Waktu dan Tempat Keberangkatan<br>Departure Time and Place</th>
    <th colspan="4">Waktu dan Tempat Tujuan<br>Arrival Time and Place</th>

    <th rowspan="2">Jml<br>Hari</th>

    <th colspan="1">Transportasi<br>Transportation</th>
    <th colspan="1">Tunjangan<br>Allowance</th>

    <th colspan="2">Hotel</th>

    <th colspan="2">Pengeluaran Lain-lain<br>Other Expenses</th>

    <th rowspan="2">Subtotal</th>
</tr>

<tr>
    <th>D</th>
    <th>M</th>
    <th>Y</th>
    <th>Tempat Berangkat</th>

    <th>D</th>
    <th>M</th>
    <th>Y</th>
    <th>Tempat Tujuan</th>

    <th>Jumlah<br>Amount</th>

    <th>Jumlah<br>Amount</th>

    <th>Hari<br>Day(s)</th>
    <th>Jumlah<br>Amount</th>

    <th>Lain-lain<br>Misc</th>
    <th>Jumlah<br>Amount</th>
</tr>
</thead>
        <tbody>
            @forelse ($details as $detail)
<tr>
<td>{{ \Carbon\Carbon::parse($detail->tanggal_berangkat)->format('d') }}</td>
<td>{{ \Carbon\Carbon::parse($detail->tanggal_berangkat)->format('m') }}</td>
<td>{{ \Carbon\Carbon::parse($detail->tanggal_berangkat)->format('Y') }}</td>
<td class="text-left">{{ $detail->tempat_berangkat }}</td>

<td>{{ \Carbon\Carbon::parse($detail->tanggal_tujuan)->format('d') }}</td>
<td>{{ \Carbon\Carbon::parse($detail->tanggal_tujuan)->format('m') }}</td>
<td>{{ \Carbon\Carbon::parse($detail->tanggal_tujuan)->format('Y') }}</td>
<td class="text-left">{{ $detail->tempat_tujuan }}</td>

<td>{{ $detail->jumlah_hari }}</td>

<td class="text-right">{{ number_format($detail->amount_transportasi,0,',','.') }}</td>

<td class="text-right">{{ number_format($detail->amount_tunjangan,0,',','.') }}</td>

<td>{{ $detail->lama_hotel }}</td>
<td class="text-right">{{ number_format($detail->amount_hotel,0,',','.') }}</td>

<td>{{ $detail->misc }}</td>
<td class="text-right">{{ number_format($detail->amount_other,0,',','.') }}</td>

<td class="text-right"><strong>{{ number_format($detail->subtotal,0,',','.') }}</strong></td>
</tr>
            @empty
            <tr>
                <td colspan="18" style="text-align: center; color: #999; font-style: italic; padding: 12px;">
                    Tidak ada detail perjalanan
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- TOTAL & TERBILANG --}}
    <table style="margin-bottom: 6px;">
        <tr>
            <td style="width: 25%; background: #f5f5f5; font-weight: bold;">Jumlah Total / Total Amount</td>
            <td style="text-align: left; font-weight: bold;">IDR {{ number_format($record->total ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="background: #f5f5f5; font-weight: bold;">Terbilang / In Words</td>
            <td style="text-align: left; font-style: italic;">{{ $record->terbilang ?? '-' }}</td>
        </tr>
        <tr>
            <td style="background: #f5f5f5; font-weight: bold; vertical-align: top;">
                Catatan/Note<br>
                <span style="font-weight: normal; font-size: 8px;">(Informasi Transfer Dana / Fund Transfer Information)</span>
            </td>
            <td style="text-align: left;">{{ $record->catatan ?? '-' }}</td>
        </tr>
    </table>

 {{-- TANDA TANGAN --}}
<div class="signature-section">
@php
    // Tentukan siapa yang menolak berdasarkan jabatan rejector,
    // karena approval_level sudah ditimpa jadi -1 saat reject
    // sehingga tidak bisa dipakai lagi untuk menentukan level penolakan.
    $isCancelled = $record->isCancelled();

    $rejectorIsLevel2 = $record->isRejected()
        && $record->rejector
        && $record->rejector->jabatan === \App\Models\Kasbon::LEVEL2_JABATAN;

    $rejectorIsAtasan = $record->isRejected() && ! $rejectorIsLevel2;

    // Atasan ditentukan dari profile pemohon, bukan dari kolom approverManager
    // (yang tidak ada di model dan tidak mencerminkan department pemohon
    // saat satu manager membawahi banyak department).
    $atasanPemohon = $record->user?->profile?->atasan;
@endphp
    <table class="signature-table">
        <tr>

            {{-- KOLOM 1: PEMOHON — selalu SUBMITTED --}}
            <td>
                Diajukan oleh / <em>Asked by</em>
                <div class="signature-space">
                    <div class="approval-stamp" style="border-color:#ff9100;color:#ff9100;">
                        ✔ SUBMITTED
                    </div>
                </div>
                <div class="signature-name">
                    {{ $record->user?->name ?? '' }}<br>
                    ({{ $record->department?->nama_department ?? '' }})
                </div>
            </td>

{{-- KOLOM 2: ATASAN --}}
<td>
    <div style="font-size:10px;">Diperiksa oleh /<br>Checked by :</div>
    <div class="signature-space">
        @include('pdf.partials.stamp', [
            'isCancelled' => $record->isCancelled(),
            'isChecked'   => (bool) $record->approved_by_manager,
            'isApproved'  => $record->isApproved(),
            'isRejected'  => $rejectorIsAtasan,
        ])
    </div>
    @if($isCancelled)
        <div class="signature-name">
            {{ $record->cancelledBy?->name ?? '-' }}<br>
            <span class="signature-title">
                {{ $record->cancelled_at?->format('d F Y, H:i') }} WIB
            </span>
        </div>
    @elseif($atasanPemohon)
        <div class="signature-name">
            {{ $atasanPemohon->name }}<br>
            ({{ $record->department?->nama_department ?? '' }})
            @if($record->approved_by_manager && $record->approved_manager_at)
                <br>
                <span class="signature-title">
                    {{ $record->approved_manager_at->format('d F Y, H:i') }} WIB
                </span>
            @endif
        </div>
    @else
        <div class="signature-name">&nbsp;</div>
    @endif
</td>

{{-- KOLOM 3: FINANCE MANAGER (approved_by) --}}
<td>
    Disetujui oleh / <em>Approved by</em>
    <div class="signature-space">
        @if($isCancelled)
            <div class="approval-stamp" style="border-color:#6b7280;color:#6b7280;">
                ✘ CANCELLED
            </div>
        @elseif($record->isApproved() && $record->approved_by)
            <div class="approval-stamp" style="border-color:#16a34a;color:#16a34a;">
                ✔ APPROVED
            </div>
        @elseif($rejectorIsLevel2)
            <div class="approval-stamp" style="border-color:#dc2626;color:#dc2626;">
                ✘ REJECTED
            </div>
        @else
            <div class="approval-stamp" style="border-color:#d97706;color:#d97706;">
                ⏳ PENDING
            </div>
        @endif
    </div>

    @if($isCancelled)
        <div class="signature-name">&nbsp;
            <div class="signature-title">Financial Manager</div>
        </div>
    @elseif($record->approvedBy && $record->isApproved())
        <div class="signature-name">
            {{ $record->approvedBy->name }}<br>
            <div class="signature-title">
                Finance Manager<br>
                {{ $record->approved_at?->format('d F Y, H:i') }} WIB
            </div>
        </div>
    @elseif($rejectorIsLevel2)
        <div class="signature-name">
            {{ $record->rejector?->name ?? '-' }}<br>
            <div class="signature-title">
                Finance Manager<br>
                {{ $record->rejected_at?->format('d F Y, H:i') }} WIB
            </div>
        </div>
    @else
        <div class="signature-name">&nbsp;
            <div class="signature-title">Financial Manager</div>
        </div>
    @endif
</td>

            {{-- KOLOM 4: VICE PRESIDENT --}}
            <td>
                Disetujui oleh / <em>Approved by</em><br><br>
                <div class="signature-name">___________</div>
                <div class="signature-title">Vice President</div>
            </td>

            {{-- KOLOM 5: PRESIDENT DIRECTOR --}}
            <td>
                Disetujui oleh / <em>Approved by</em><br><br>
                <div class="signature-name">___________</div>
                <div class="signature-title">President Director</div>
            </td>

        </tr>
    </table>
</div>

</body>
</html>
