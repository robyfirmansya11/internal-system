<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 22px 26px;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .header-table td {
            border: none;
            vertical-align: middle;
            padding: 2px 6px;
        }
        .logo-cell { width: 100px; text-align: center; }
        .logo-cell img { width: 76px; }

        .title-cell { text-align: center; }
        .title-cell .doc-no {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3px;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .title-cell .red-line {
            border-bottom: 2px solid #c0392b;
            margin-bottom: 5px;
        }
        .title-cell .form-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a5f;
        }

        /* INFO ROWS */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            margin-top: 10px;
        }
        .info-table td {
            border: none;
            padding: 3px 6px;
            font-size: 10.5px;
            color: #333;
        }
        .info-table strong { color: #1e3a5f; }

        /* MAIN TABLE */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .main-table th, .main-table td {
            border: 1px solid #999;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
            font-size: 9.5px;
        }
        .main-table thead tr:first-child th {
            background-color: #f0f3f7;
            font-weight: bold;
            font-size: 9.5px;
            color: #1e3a5f;
        }
        .main-table .th-group {
            background-color: #f0f3f7;
            color: #1e3a5f;
        }
        .main-table td.left-align {
            text-align: left;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f5f5f5;
            color: #1e3a5f;
        }

        /* SIGNATURE */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 28px;
        }
        .signature-table td {
            border: none;
            text-align: center;
            padding: 8px;
            vertical-align: top;
            font-size: 10.5px;
        }
        .sig-box {
            border: 1px solid #999;
            border-radius: 4px;
            height: 66px;
            margin-bottom: 6px;
            background-color: #fafafa;
        }
        .sig-label {
            font-weight: bold;
            color: #1e3a5f;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sig-name {
            font-weight: bold;
            color: #111;
            margin-top: 2px;
        }
        .sig-role {
            color: #777;
            font-size: 9.5px;
            margin-top: 1px;
        }

        /* FOOTER */
        .doc-footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 8.5px;
            color: #999;
            text-align: right;
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
                @if($nama)
                    <span>Nama &nbsp;: <strong>{{ $nama }}</strong></span>
                @endif
            </td>
            <td style="width: 50%;">
                <span>Periode / Period &nbsp;:
                    <strong>{{ $bulan_tahun ?? '_______________' }}</strong>
                </span>
            </td>
        </tr>
        @if($department)
        <tr>
            <td>
                <span>Department &nbsp;: <strong>{{ $department }}</strong></span>
            </td>
            <td></td>
        </tr>
        @endif
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
                        {{ $row->mulai_kerja ? Carbon::parse($row->mulai_kerja)->format('H:i') : '-' }}
                    </td>
                    <td>
                        {{ $row->selesai_kerja ? Carbon::parse($row->selesai_kerja)->format('H:i') : '-' }}
                    </td>
                    <td>
                        {{ $row->mulai_lembur ? Carbon::parse($row->mulai_lembur)->format('H:i') : '-' }}
                    </td>
                    <td>
                        {{ $row->selesai_lembur ? Carbon::parse($row->selesai_lembur)->format('H:i') : '-' }}
                    </td>
                    <td>
                        Rp {{ number_format((float) str_replace(',', '', $row->uang_makan ?? 0), 0, ',', '.') }}
                    </td>
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
    <table class="signature-table">
        <tr>
            <td>
                <div class="sig-label">Dibuat Oleh / <em>Prepared by</em></div>
                <div class="sig-box"></div>
                <div class="sig-name">( {{ $nama ?? '____________________' }} )</div>
                <div class="sig-role">Karyawan / <em>Employee</em></div>
            </td>
            <td>
                <div class="sig-label">Disetujui Oleh / <em>Approved by</em></div>
                <div class="sig-box"></div>
                <div class="sig-name">( ______________________ )</div>
                <div class="sig-role">Atasan / <em>Supervisor</em></div>
            </td>
            <td>
                <div class="sig-label">Mengetahui / <em>Acknowledged by</em></div>
                <div class="sig-box"></div>
                <div class="sig-name">( ______________________ )</div>
                <div class="sig-role">HRD / <em>HR Department</em></div>
            </td>
        </tr>
    </table>

    {{-- ====== FOOTER ====== --}}
    <div class="doc-footer">
        Generated on {{ now()->format('d F Y H:i') }} WIB
    </div>

</body>
</html>
