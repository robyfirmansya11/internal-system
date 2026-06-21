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
    </table>

{{-- TANDA TANGAN --}}
<div class="signature-section">
<table class="signature-table" style="margin-top:0px;">
<tr>

{{-- DIAJUKAN --}}
<td>
    <div style="font-size:10px;">Diajukan oleh /<br>Submitted by :</div>

    <div class="signature-space">
            <div style="
                position:absolute;
                top:50%;
                left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #ff9100;
                border-radius:6px;
                padding:3px 10px;
                color:#ff9100;
                font-size:14px;
                font-weight:bold;
                letter-spacing:2px;
                opacity:0.6;
            ">✔ SUBMITTED</div>
    </div>

    <div class="signature-name">
        {{ $record->user?->name ?? '' }}<br>
        ({{ $record->department?->nama_department ?? '' }})
    </div>
</td>

{{-- DIPERIKSA --}}
<td>
    <div style="font-size:10px;">Diperiksa oleh /<br>Checked by :</div>

    <div class="signature-space">

        @if($record->isApproved())
            <div style="
                position:absolute;
                top:50%;
                left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #16a34a;
                border-radius:6px;
                padding:3px 10px;
                color:#16a34a;
                font-size:14px;
                font-weight:bold;
                letter-spacing:2px;
                opacity:0.6;
            ">✔ APPROVED</div>

       @elseif($record->isRejected())
            <div style="
                position:absolute;
                top:50%;
                left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #dc2626;
                border-radius:6px;
                padding:3px 10px;
                color:#dc2626;
                font-size:14px;
                font-weight:bold;
                letter-spacing:2px;
                opacity:0.6;
            ">✘ REJECTED</div>

        @else
            <div style="
                position:absolute;
                top:50%;
                left:50%;
                transform:translate(-50%,-50%) rotate(-15deg);
                border:3px solid #d97706;
                border-radius:6px;
                padding:3px 10px;
                color:#d97706;
                font-size:14px;
                font-weight:bold;
                letter-spacing:2px;
                opacity:0.6;
            ">⏳ PENDING</div>
        @endif

    </div>

    @if($record->approved_by)
        <div class="signature-name">
            {{ $record->approver->name ?? '-' }}<br>
            ({{ $record->approver->departments->first()->nama_department ?? '' }})<br>
        </div>
    @else
        <div class="signature-name">&nbsp;</div>
    @endif
</td>

{{-- FINANCE --}}
<td>
    <div style="font-size:10px;">Disetujui oleh /<br>Approved by :</div>
    <div class="signature-space"></div>
    <div class="signature-name">&nbsp;
        Financial Manager
    </div>
</td>

{{-- VP --}}
<td>
    <div style="font-size:10px;">Disetujui oleh /<br>Approved by :</div>
    <div class="signature-space"></div>
    <div class="signature-name">&nbsp;
        Vice President
    </div>
</td>

{{-- DIRECTOR --}}
<td>
    <div style="font-size:10px;">Disetujui oleh /<br>Approved by :</div>
    <div class="signature-space"></div>
    <div class="signature-name">&nbsp;
        President Director
    </div>
</td>

</tr>
</table>
</div>

</div>
</body>
</html>
