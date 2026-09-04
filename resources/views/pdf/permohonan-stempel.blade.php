<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Serif', serif;
            font-size: 12px;
            color: #111;
            background: #fff;
            padding: 32px 40px;
        }

        /* ─── HEADER ─── */
        .doc-header {
            text-align: center;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e3a5f;
        }

        .doc-header::before {
            content: '';
            display: block;
            width: 40px;
            height: 4px;
            background: #1e3a5f;
            margin: 0 auto 14px;
            border-radius: 2px;
        }

        .doc-title {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #1e3a5f;
            margin-bottom: 4px;
        }

        .doc-subtitle {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #666;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ─── INFO TABLE ─── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            border: 1px solid #ccc;
        }

        .info-table td {
            padding: 9px 13px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        .info-table .label {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            background-color: #f5f5f5;
            width: 140px;
            white-space: nowrap;
        }

        .info-table .value {
            font-size: 12px;
            color: #111;
        }

        .info-table .value.company {
            font-weight: bold;
            font-size: 13px;
            color: #1e3a5f;
        }

        /* ─── SECTION DIVIDER ─── */
        .section-divider {
            text-align: center;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #888;
            margin: 24px 0 16px;
            position: relative;
        }

        .section-divider::before,
        .section-divider::after {
            content: '────────────────────';
            color: #ddd;
            margin: 0 10px;
        }

        /* ─── SIGNATURE AREA ─── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }

        .sig-table td:first-child {
            padding-left: 0;
            padding-right: 16px;
        }

        .sig-table td:last-child {
            padding-left: 16px;
            padding-right: 0;
        }

        .sig-block {
            border: 1px solid #ccc;
            border-radius: 6px;
            overflow: hidden;
        }

        .sig-block-header {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #666;
            background: #f5f5f5;
            padding: 8px 14px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        .sig-block-body {
            padding: 16px 12px 14px;
            min-height: 110px;
            position: relative;
            text-align: center;
        }

        /* ─── STAMP ─── */
        .stamp {
            display: inline-block;
            border: 2px solid;
            border-radius: 6px;
            padding: 5px 14px;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 3px;
            opacity: 0.8;
            margin-bottom: 14px;
            transform: rotate(-10deg);
        }

        .stamp-submitted { border-color: #b45309; color: #b45309; }
        .stamp-approved  { border-color: #166534; color: #166534; }
        .stamp-rejected  { border-color: #991b1b; color: #991b1b; }
        .stamp-pending   { border-color: #92400e; color: #92400e; }
        .stamp-cancelled { border-color: #6b7280; color: #6b7280; }

        /* ─── SIGNATURE NAME ─── */
.status-cancelled { color: #6b7280; }


        .sig-name-area {
            border-top: 1px solid #ccc;
            padding-top: 8px;
            margin-top: 4px;
        }

        .sig-name {
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: bold;
            font-size: 12px;
            color: #111;
            margin-bottom: 2px;
        }

        .sig-dept {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #666;
        }

        /* ─── FOOTER ─── */
        .doc-footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #999;
            display: table;
            width: 100%;
        }

        .doc-footer .footer-left  { display: table-cell; text-align: left; }
        .doc-footer .footer-right { display: table-cell; text-align: right; }

        .status-label {
            font-weight: bold;
        }

        .status-approved { color: #166534; }
        .status-rejected { color: #991b1b; }
        .status-pending  { color: #92400e; }
    </style>
</head>
<body>

    {{-- ═══ HEADER ═══ --}}
    <div class="doc-header">
        <div class="doc-title">Surat Permohonan Stempel</div>
        <div class="doc-subtitle">Stamp Application Letter</div>
    </div>

    {{-- ═══ INFO TABLE ═══ --}}
    <table class="info-table">
        <tr>
            <td class="label">Perusahaan / PT</td>
            <td class="value company" colspan="3">{{ $record->company->nama }}</td>
        </tr>
        <tr>
            <td class="label">Nama / Name</td>
            <td class="value">{{ $record->user->name }}</td>
            <td class="label">Department</td>
            <td class="value">{{ $record->department->nama_department }}</td>
        </tr>
        <tr>
            <td class="label">Keterangan</td>
            <td class="value" colspan="3">{{ $record->keterangan }}</td>
        </tr>
        <tr>
            <td class="label">Tujuan</td>
            <td class="value" colspan="3">{{ $record->tujuan }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Surat</td>
            <td class="value">{{ $record->tanggal_surat }}</td>
            <td class="label">Nomor Surat</td>
            <td class="value">{{ $record->nomor_surat }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Stempel</td>
            <td class="value">{{ $record->tanggal_stempel }}</td>
            <td class="label">Ditandatangani oleh</td>
            <td class="value">{{ $record->ditandatangani_oleh }}</td>
        </tr>

        @if($record->isRejected() && $record->rejected_note)
        <tr>
            <td class="label" style="color:#991b1b;">Catatan Penolakan</td>
            <td class="value" colspan="3" style="color:#991b1b;">{{ $record->rejected_note }}</td>
        </tr>
        @endif
    </table>

    {{-- ═══ SECTION DIVIDER ═══ --}}
    <div class="section-divider">Tanda Tangan</div>

    {{-- ═══ SIGNATURE BLOCKS ═══ --}}
    @php
        $jabatanPemohon = $record->user?->jabatan;
        $labelPemohon = in_array($jabatanPemohon, ['Manager', 'Finance Manager'], true)
            ? $jabatanPemohon
            : $record->department?->nama_department;
    @endphp
    <table class="sig-table">
        <tr>

            {{-- Diajukan --}}
            <td>
                <div class="sig-block">
                    <div class="sig-block-header">Diajukan oleh</div>
                    <div class="sig-block-body">
                        <div><span class="stamp stamp-submitted">SUBMITTED</span></div>
                        <div class="sig-name-area">
                            <div class="sig-name">{{ $record->user->name }}</div>
                            <div class="sig-dept">{{ $labelPemohon ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </td>

       {{-- Diperiksa --}}
<td>
    <div class="sig-block">
        <div class="sig-block-header">Diperiksa oleh</div>
        <div class="sig-block-body">

            @if($record->isCancelled())
                <div><span class="stamp stamp-cancelled">CANCELLED</span></div>
            @elseif($record->isApproved())
                <div><span class="stamp stamp-approved">APPROVED</span></div>
            @elseif($record->isRejected())
                <div><span class="stamp stamp-rejected">REJECTED</span></div>
            @else
                <div><span class="stamp stamp-pending">PENDING</span></div>
            @endif

            <div class="sig-name-area">
                @if($record->isCancelled())
                    <div class="sig-name">{{ $record->cancelledBy?->name ?? '-' }}</div>
                    <div class="sig-dept">{{ $record->cancelled_at?->format('d F Y, H:i') }} WIB</div>
                @elseif($record->approved_by)
                    <div class="sig-name">{{ $record->approver->name }}</div>
                    <div class="sig-dept">{{ $record->approver?->jabatan ?? '-' }}</div>
                @elseif($record->isRejected())
                    <div class="sig-name">{{ $record->rejector?->name ?? '-' }}</div>
                    <div class="sig-dept">{{ $record->rejected_at?->format('d F Y, H:i') }} WIB</div>
                @else
                    <div class="sig-name">&nbsp;</div>
                    <div class="sig-dept">&nbsp;</div>
                @endif
            </div>

        </div>
    </div>
</td>

        </tr>
    </table>

 {{-- ═══ FOOTER ═══ --}}
<div class="doc-footer">
    <div class="footer-left">Dokumen ini dicetak secara otomatis oleh sistem</div>
    <div class="footer-right">
        Status:&nbsp;
        @if($record->isCancelled())
            <span class="status-label status-cancelled">● Cancelled</span>
        @elseif($record->isApproved())
            <span class="status-label status-approved">● Approved</span>
        @elseif($record->isRejected())
            <span class="status-label status-rejected">● Rejected</span>
        @else
            <span class="status-label status-pending">● Pending</span>
        @endif
    </div>
</div>

</body>
</html>
