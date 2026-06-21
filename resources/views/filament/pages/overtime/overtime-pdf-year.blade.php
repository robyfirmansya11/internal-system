<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; padding: 20px; }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .header-table td {
            border: none;
            vertical-align: middle;
            padding: 2px 6px;
        }
        .logo-cell { width: 100px; text-align: center; }
        .logo-cell img { width: 80px; }

        .title-cell { text-align: center; }
        .title-cell .doc-no {
            font-size: 13px;
            font-weight: bold;
            border-bottom: 2px solid black;
            padding-bottom: 2px;
            margin-bottom: 2px;
        }
        .title-cell .red-line {
            border-bottom: 2px solid red;
            margin-bottom: 4px;
        }
        .title-cell .form-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* INFO ROWS */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            margin-top: 8px;
        }
        .info-table td {
            border: none;
            padding: 2px 4px;
            font-size: 11px;
        }

        /* MAIN TABLE */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .main-table th, .main-table td {
            border: 1px solid black;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
        }
        .main-table thead tr:first-child th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }
        .main-table .th-group {
            background-color: #f0f0f0;
        }
        .main-table td.left-align {
            text-align: left;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        /* SIGNATURE */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .signature-table td {
            border: none;
            text-align: center;
            padding: 8px;
            vertical-align: top;
            font-size: 11px;
        }
        .sig-box {
            border: 1px solid black;
            height: 70px;
            margin-bottom: 5px;
        }
        .sig-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
    use Carbon\Carbon;
@endphp

    {{-- ====== HEADER ====== --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell" style="width: 110px;">
                <img src="{{ public_path('ASI.PNG') }}" alt="Logo">
            </td>
            <td class="title-cell">
                <div class="doc-no">FM/HRD/011.00</div>
                <div class="red-line"></div>
                <div class="form-title">Laporan Lembur / Overtime Report</div>
            </td>
            <td style="width: 110px;"></td>
        </tr>
    </table>

{{-- ====== INFO ====== --}}
<table class="info-table">
    <tr>
        <td style="width: 50%;">
            <span>Tahun / Year &nbsp;:
                <strong>{{ $tahun ?? '_______________' }}</strong>
            </span>
        </td>
        <td style="width: 50%;">
            <span>Total Data &nbsp;:
                <strong>{{ $total_records ?? '_______________' }}</strong>
            </span>
        </td>
    </tr>
</table>

    {{-- ====== TABEL LEMBUR ====== --}}
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 70px;">Tanggal<br><em>Date</em></th>
                <th colspan="2" class="th-group">Jam Kerja<br><em>Working Hours</em></th>
                <th colspan="2" class="th-group">Lembur<br><em>Overtime</em></th>
                <th rowspan="2" style="width: 55px;">Uang Makan<br><em>Meal Allow.</em></th>
                <th rowspan="2">Uraian Pekerjaan<br><em>Job Description</em></th>
                <th rowspan="2" style="width: 55px;">Jumlah Jam Lembur<br><em>Total OT Hours</em></th>
            </tr>
            <tr>
                <th style="width: 65px;">Mulai<br><em>Start</em></th>
                <th style="width: 65px;">Selesai<br><em>End</em></th>
                <th style="width: 65px;">Mulai<br><em>Start</em></th>
                <th style="width: 65px;">Selesai<br><em>End</em></th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    <td>
   {{ Carbon::parse($row->tanggal_lembur)->format('d/m/Y') }}
</td>
<td>
    {{ $row->mulai_kerja ? \Carbon\Carbon::parse($row->mulai_kerja)->format('H:i') : '-' }}
</td>

<td>
    {{ $row->selesai_kerja ? \Carbon\Carbon::parse($row->selesai_kerja)->format('H:i') : '-' }}
</td>
<td>
    {{ $row->mulai_lembur ? \Carbon\Carbon::parse($row->mulai_lembur)->format('H:i') : '-' }}
</td>

<td>
    {{ $row->selesai_lembur ? \Carbon\Carbon::parse($row->selesai_lembur)->format('H:i') : '-' }}
</td>
                    <td>{{ $row->uang_makan ?? '-' }}</td>
                    <td class="left-align">{{ $row->uraian_pekerjaan ?? '' }}</td>
                    <td>{{ $row->jumlah_jam_lembur ?? '' }}</td>
                </tr>
            @empty
                {{-- Empty rows for blank form --}}
                @for($i = 0; $i < 26; $i++)
                <tr style="height: 22px;">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
            @endforelse

            {{-- TOTAL --}}
            <tr class="total-row">
                <td colspan="7" style="text-align: right; padding-right: 10px;">
                    Total Jam Lembur / <em>Total Overtime Hours</em>
                </td>
                <td>{{ $totalJam ?? '' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ====== TANDA TANGAN ====== --}}
    <table class="signature-table" style="margin-top: 20px;">
        <tr>
            <td>
                <div class="sig-label">Dibuat Oleh / <em>Prepared by</em></div>
                <div class="sig-box"></div>
                <div>( {{ $nama ?? '____________________' }} )</div>
                <div>Karyawan / <em>Employee</em></div>
            </td>
            <td>
                <div class="sig-label">Disetujui Oleh / <em>Approved by</em></div>
                <div class="sig-box"></div>
                <div>( ______________________ )</div>
                <div>Atasan / <em>Supervisor</em></div>
            </td>
            <td>
                <div class="sig-label">Mengetahui / <em>Acknowledged by</em></div>
                <div class="sig-box"></div>
                <div>( ______________________ )</div>
                <div>HRD / <em>HR Department</em></div>
            </td>
        </tr>
    </table>

</body>
</html>
