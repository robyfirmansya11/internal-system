{{-- resources/views/pdf/form-cuti.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Permohonan Cuti</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif, DejaVu Sans, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            padding: 24px 28px;
        }

        /* ── TOP BAR ── */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #c00;
            padding-bottom: 6px;
            margin-bottom: 2px;
        }

        .top-bar .logo-box {
            width: 52px;
            height: 44px;
            border: 2px solid #8b0000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #8b0000;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .top-bar .form-code {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            flex: 1;
        }

        /* ── TITLE ── */
        .title-section {
            text-align: center;
            margin: 6px 0 10px;
        }

        .title-section h1 {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .title-section h2 {
            font-size: 10px;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        /* ── META ROW (Nama & Bulan/Tahun) ── */
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 10px;
        }

        /* ── TABLES ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 9px;
            vertical-align: middle;
        }

        table th {
            background-color: #e6e6e6;
            font-weight: bold;
            text-align: center;
        }

        tbody tr {
            height: 22px;
        }

        .text-left  { text-align: left; }
        .text-right { text-align: right; }
        .text-center{ text-align: center; }
        .label-col  { background: #e6e6e6; font-weight: bold; width: 30%; }

        /* ── STATUS STAMP ── */
        .status-stamp {
            display: inline-block;
            border: 2px solid;
            border-radius: 4px;
            padding: 2px 8px;
            font-weight: bold;
            font-size: 9px;
            letter-spacing: 1px;
        }
        .stamp-approved { border-color: #16a34a; color: #16a34a; }
        .stamp-rejected { border-color: #dc2626; color: #dc2626; }
        .stamp-waiting  { border-color: #d97706; color: #d97706; }

        /* ── TOTAL ROW ── */
        .total-row td {
            font-weight: bold;
            text-align: right;
            background: #f5f5f5;
        }

        /* ── SIGNATURE ── */
        .signature-section {
            margin-top: 16px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            width: 33.33%;
            vertical-align: top;
        }

        .signature-section {
    margin-top: 20px;
}

.signature-table {
    width: 100%;
    border-collapse: collapse;
}

.signature-table td {
    border: 1px solid #000;
    padding: 8px 6px;
    text-align: center;
    width: 20%;
    vertical-align: top;
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
    config/dompdf.php
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
    </style>
</head>
<body>

    {{-- ══ TITLE ══ --}}
    <div class="title-section">
        <h1>Form Permohonan Cuti</h1>
        <h2>Leave Request Form</h2>
    </div>

    {{-- ══ META ROW ══ --}}
<table style="width:100%; margin-bottom:8px; border:none;">
<tr>
    <td style="border:none; text-align:left;">
        <strong>Nama / Name :</strong> {{ $cuti->user->name }}
    </td>

    <td style="border:none; text-align:right;">
        <strong>Bulan/Tahun (Month/Year) :</strong>
        {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->translatedFormat('F Y') }}
    </td>
</tr>
</table>
    {{-- ══ INFO KARYAWAN ══ --}}
    <table style="margin-bottom: 6px;">
        <tr>
            <td class="label-col">Nama / Name</td>
            <td class="text-left">{{ $cuti->user->name }}</td>
    <td class="label-col" style="width:22%; align:right">Tahun / Year</td>
    <td class="text-left" style="width:18%;">{{ $cuti->tahun }}</td>
        </tr>
        <tr>
            <td class="label-col">Departemen / Department</td>
            <td class="text-left">{{ $cuti->department->nama_department }}</td>
            <td class="label-col"></td>
            <td class="text-left">
            </td>
        </tr>

        @if($cuti->rejected_note)
        <tr>
            <td class="label-col" style="color:#c00;">Catatan Penolakan / Rejected Note</td>
            <td colspan="3" class="text-left" style="color:#c00;">{{ $cuti->rejected_note }}</td>
        </tr>
        @endif
    </table>

    {{-- ══ TABEL DETAIL CUTI ══ --}}
    <table>
        <thead>
            <tr>
                <th>Tanggal Mulai<br><em style="font-weight:normal;">Start Date</em></th>
                <th>Tanggal Selesai<br><em style="font-weight:normal;">End Date</em></th>
                <th>Jumlah Hari<br><em style="font-weight:normal;">Total Days</em></th>
                <th>Keterangan<br><em style="font-weight:normal;">Remarks</em></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">
                    {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->format('d M Y') }}
                </td>
                <td class="text-center">
                    {{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->format('d M Y') }}
                </td>
                <td class="text-center">{{ $cuti->jumlah_hari }}</td>
                <td class="text-left">{{ $cuti->alasan }}</td>
            </tr>
            {{-- baris kosong agar tabel terlihat lebih formal --}}
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
            <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align:right; background:#e6e6e6;">
                    Total Hari Cuti / Total Leave Days
                </td>
                <td class="text-center">{{ $cuti->jumlah_hari }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

@php
    $managerName = ($cuti->approval_level >= 1) ? $cuti->approvedBy?->name : '-';
@endphp

<div class="signature-section">
    <table class="signature-table">
        <tr>

            {{-- DIAJUKAN --}}
            <td>
                Diajukan oleh / <em>Asked by</em>

                <div class="signature-space">
                    <div class="approval-stamp" style="border-color:#16a34a;color:#16a34a;">
                        ✔ SUBMITTED
                    </div>
                </div>

                <div class="signature-name">
                    {{ $cuti->user?->name }}<br>
                    ({{ $cuti->department?->nama_department }})
                </div>
            </td>

            {{-- MANAGER --}}
            <td>
                Diperiksa oleh / <em>Checked by</em>

                <div class="signature-space">

                    @if($cuti->approval_level >= 1)
                        <div class="approval-stamp" style="border-color:#16a34a;color:#16a34a;">
                            ✔ APPROVED
                        </div>

                    @elseif($cuti->status === 'Rejected')
                        <div class="approval-stamp" style="border-color:#dc2626;color:#dc2626;">
                            ✘ REJECTED
                        </div>

                    @else
                        <div class="approval-stamp" style="border-color:#d97706;color:#d97706;">
                            ⏳ WAITING
                        </div>
                    @endif

                </div>

<div class="signature-name">
    {{ $cuti->manager?->name ?? '-' }} <br>
({{ $cuti->department?->nama_department ?? '-' }})
</div>
            </td>

            {{-- HRD --}}
            <td>
                Disetujui oleh / <em>Approved by</em>

                <div class="signature-space">

                    @if($cuti->approval_level >= 2)
                        <div class="approval-stamp" style="border-color:#16a34a;color:#16a34a;">
                            ✔ APPROVED
                        </div>

                    @elseif($cuti->approval_level == 1)
                        <div class="approval-stamp" style="border-color:#d97706;color:#d97706;">
                            ⏳ WAITING
                        </div>

                    @elseif($cuti->status === 'Rejected')
                        <div class="approval-stamp" style="border-color:#dc2626;color:#dc2626;">
                            ✘ REJECTED
                        </div>

                    @else
                        <div class="approval-stamp" style="border-color:#999;color:#999;">
                            -
                        </div>
                    @endif

                </div>

<div class="signature-name">
    {{ $cuti->hrd?->name ?? '-' }}<br>
({{ $cuti->hrd?->departments->pluck('nama_department')->join(', ') ?? '-' }})
</div>
            </td>

        </tr>
    </table>
</div>
</body>
</html>
