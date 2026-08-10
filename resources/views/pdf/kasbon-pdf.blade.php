<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasbon / Loan Note</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', 'Times', 'serif', 'Arial', 'sans-serif', 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #000;
            padding: 20px;
            background: #fff;
        }
        .document {
            max-width: 900px;
            margin: auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 11px;
            font-weight: normal;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* Meta row: PT + tanggal kotak */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .meta-table td {
            border: none;
            padding: 2px 0;
            font-size: 10px;
        }
        .tanggal-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 1px 8px;
            min-width: 28px;
            text-align: center;
        }

        /* Main info table */
        table.main {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.main td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10px;
        }
        table.main td.label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 28%;
        }
        td.keterangan-cell {
            height: 60px;
        }

        /* Signature section */
        .signature-section {
            margin-top: 16px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
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
        .approval-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            border: 3px solid;
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            opacity: 0.6;
            white-space: nowrap;
        }
        .signature-name {
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }
        .signature-title {
            font-size: 8px;
            margin-top: 2px;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="document">

    {{-- HEADER --}}
    <div class="header">
        <h1>K A S B O N</h1>
        <h2>L O A N &nbsp; N O T E</h2>
    </div>

    {{-- META: PT & TANGGAL --}}
    <table class="meta-table">
        <tr>
            <td style="width: 60%; text-align: left;">
                <strong>PT:</strong> {{ $record->company?->kode ?? '-' }}
            </td>
            <td style="text-align: right;">
                D <span class="tanggal-box">{{ $record->tanggal?->format('d') }}</span>
                &nbsp;M <span class="tanggal-box">{{ $record->tanggal?->format('m') }}</span>
                &nbsp;Y <span class="tanggal-box">{{ $record->tanggal?->format('Y') }}</span>
            </td>
        </tr>
    </table>

    {{-- INFO UTAMA --}}
    <table class="main">
        <tr>
            <td class="label">Nama / Name</td>
            <td colspan="3">{{ $record->user?->name ?? '-' }}</td>
            <td class="label" style="width: 18%;">Department</td>
            <td>{{ $record->department?->nama_department ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Keterangan / Description</td>
            <td colspan="5" class="keterangan-cell">{{ $record->keterangan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Dana / Amount</td>
            <td colspan="5">IDR {{ number_format($record->jumlah_dana ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Terbilang / In Words</td>
            <td colspan="5" style="font-style: italic;">{{ $record->terbilang ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">
                Informasi Transfer Dana<br>
                <span style="font-weight: normal; font-size: 8px;">Fund Transfer Information</span>
            </td>
            <td colspan="5">{{ $record->informasi_transfer ?? '-' }}</td>
        </tr>

        @if($record->isRejected() && $record->rejected_note)
        <tr>
            <td class="label" style="color:#dc2626;">Catatan Penolakan / Rejected Note</td>
            <td colspan="5" style="color:#dc2626;">{{ $record->rejected_note }}</td>
        </tr>
        @endif
    </table>

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

{{-- TANDA TANGAN --}}
<div class="signature-section">
<table class="signature-table" style="margin-top:0px;">
<tr>

{{-- KOLOM 1: PEMOHON — selalu SUBMITTED --}}
<td>
    <div style="font-size:10px;">Diajukan oleh /<br>Submitted by :</div>

    <div class="signature-space">
        <div style="
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-50%) rotate(-15deg);
            border:3px solid #ff9100; border-radius:6px;
            padding:3px 10px; color:#ff9100;
            font-size:14px; font-weight:bold;
            letter-spacing:2px; opacity:0.6;
        ">✔ SUBMITTED</div>
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

{{-- KOLOM 3: FINANCE MANAGER --}}
<td>
    <div style="font-size:10px;">Disetujui oleh /<br>Approved by :</div>
    <div class="signature-space">
        @if($isCancelled)
            <div style="position:absolute;top:50%;left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #6b7280;border-radius:6px;
                padding:3px 10px;color:#6b7280;
                font-size:14px;font-weight:bold;
                letter-spacing:2px;opacity:0.6;">
                ✘ CANCELLED
            </div>
        @elseif($record->isApproved() && $record->approved_by)
            <div style="position:absolute;top:50%;left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #16a34a;border-radius:6px;
                padding:3px 10px;color:#16a34a;
                font-size:14px;font-weight:bold;
                letter-spacing:2px;opacity:0.6;">
                ✔ APPROVED
            </div>
        @elseif($record->isRejected())
            <div style="position:absolute;top:50%;left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #dc2626;border-radius:6px;
                padding:3px 10px;color:#dc2626;
                font-size:14px;font-weight:bold;
                letter-spacing:2px;opacity:0.6;">
                ✘ REJECTED
            </div>
        @else
            <div style="position:absolute;top:50%;left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #d97706;border-radius:6px;
                padding:3px 10px;color:#d97706;
                font-size:14px;font-weight:bold;
                letter-spacing:2px;opacity:0.6;">
                ⏳ PENDING
            </div>
        @endif
    </div>
    @if($isCancelled)
        <div class="signature-name">&nbsp;
            <span class="signature-title">Financial Manager</span>
        </div>
    @elseif($record->approver && $record->isApproved())
        <div class="signature-name">
            {{ $record->approver->name }}<br>
            <span class="signature-title">Financial Manager<br>
                {{ $record->approved_at?->format('d F Y, H:i') }} WIB
            </span>
        </div>
    @else
        <div class="signature-name">&nbsp;
            <span class="signature-title">Financial Manager</span>
        </div>
    @endif
</td>

{{-- KOLOM 4: VP --}}
<td>
    <div style="font-size:10px;">Disetujui oleh /<br>Approved by :</div>
    <div class="signature-space"></div>
    <div class="signature-name">&nbsp;
        <span class="signature-title">Vice President</span>
    </div>
</td>

{{-- KOLOM 5: DIRECTOR --}}
<td>
    <div style="font-size:10px;">Disetujui oleh /<br>Approved by :</div>
    <div class="signature-space"></div>
    <div class="signature-name">&nbsp;
        <span class="signature-title">President Director</span>
    </div>
</td>

</tr>
</table>
</div>

</div>
</body>
</html>
