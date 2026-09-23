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
            color: #1a1a1a;
            padding: 24px 28px;
        }

        /* ── TOP BAR ── */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 6px;
            margin-bottom: 2px;
        }

        .top-bar .logo-box {
            width: 52px;
            height: 44px;
            border: 2px solid #1e3a5f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #1e3a5f;
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
            color: #1e3a5f;
        }

        .title-section h2 {
            font-size: 10px;
            font-weight: normal;
            letter-spacing: 0.5px;
            color: #666;
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
            border: 1px solid #999;
            padding: 4px 6px;
            font-size: 9px;
            vertical-align: middle;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            color: #444;
        }

        tbody tr {
            height: 22px;
        }

        .text-left  { text-align: left; }
        .text-right { text-align: right; }
        .text-center{ text-align: center; }
        .label-col  { background: #f0f0f0; font-weight: bold; width: 30%; color: #333; }

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
            color: #1e3a5f;
        }

        /* ── SIGNATURE ── */
        .signature-section {
            margin-top: 20px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            border: 1px solid #999;
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
            opacity: 0.7;
            white-space: nowrap;
        }

        .signature-name {
            margin-top: 50px;
            border-top: 1px solid #999;
            padding-top: 4px;
            font-weight: bold;
        }

        .signature-title {
            font-size: 9px;
            margin-top: 2px;
            color: #666;
            font-weight: normal;
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
            <td class="text-left"></td>
        </tr>

        @if($cuti->rejected_note)
        <tr>
            <td class="label-col" style="color:#dc2626;">Catatan Penolakan / Rejected Note</td>
            <td colspan="3" class="text-left" style="color:#dc2626;">{{ $cuti->rejected_note }}</td>
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
                <td colspan="2" style="text-align:right; background:#f0f0f0;">
                    Total Hari Cuti / Total Leave Days
                </td>
                <td class="text-center">{{ $cuti->jumlah_hari }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @php
        $isCancelled = $cuti->isCancelled();

        // approval_level berubah menjadi -1 setelah reject, jadi penolak
        // digunakan untuk menentukan kolom tanda tangan yang relevan.
        $rejectorIsHrd = $cuti->isRejected()
            && $cuti->rejector
            && $cuti->rejector->jabatan === \App\Models\FormCuti::LEVEL2_JABATAN;

        $atasanPemohon = $cuti->user?->profile?->atasan;
        $jabatanPemohon = $cuti->user?->jabatan;
        $isManagerApplicant = $jabatanPemohon === 'Manager';
        $isFinanceManagerApplicant = $jabatanPemohon === 'Finance Manager';
        $isHrdApplicant = $jabatanPemohon === \App\Models\FormCuti::LEVEL2_JABATAN;

        // Manager diperiksa Finance Manager. Finance Manager langsung ke HRD.
        // HRD diperiksa sekaligus disetujui final oleh atasannya.
        $checkerNotRequired = $isFinanceManagerApplicant;
        $checkerIsFinalApprover = $isHrdApplicant;
        $checker = $isManagerApplicant ? $cuti->financeManager : $atasanPemohon;
        $checkerApproved = $isManagerApplicant
            ? filled($cuti->approved_by_finance_manager)
            : filled($cuti->approved_by_manager);
        $checkerApprovedAt = $isManagerApplicant
            ? $cuti->approved_finance_manager_at
            : $cuti->approved_manager_at;
        $rejectorIsChecker = $cuti->isRejected()
            && ! $rejectorIsHrd
            && ! $checkerNotRequired;
        $labelPemohon = in_array($jabatanPemohon, ['Manager', 'Finance Manager'], true)
            ? $jabatanPemohon
            : $cuti->department?->nama_department;
    @endphp

    <div class="signature-section">
        <table class="signature-table">
            <tr>

                {{-- KOLOM 1: PEMOHON — selalu SUBMITTED --}}
                <td>
                    Diajukan oleh / <em>Asked by</em>

                    <div class="signature-space">
                        <div class="approval-stamp" style="border-color:#b45309;color:#b45309;">
                            ✔ SUBMITTED
                        </div>
                    </div>

                    <div class="signature-name">
                        {{ $cuti->user?->name }}<br>
                        <span class="signature-title">({{ $labelPemohon ?? '' }})</span>
                    </div>
                </td>

                {{-- KOLOM 2: Atasan, Finance Manager, atau N/A sesuai alur pemohon --}}
                <td>
                    @if($checkerIsFinalApprover || $checkerApproved)
                        Disetujui oleh / <em>Approved by</em>
                    @elseif($checkerNotRequired)
                        Pemeriksaan / <em>Review</em>
                    @else
                        Diperiksa oleh / <em>Checked by</em>
                    @endif

                    <div class="signature-space">
                        @if($checkerNotRequired)
                            <div class="approval-stamp" style="border-color:#9ca3af;color:#9ca3af;">
                                N/A
                            </div>
                        @elseif($isCancelled)
                            <div class="approval-stamp" style="border-color:#6b7280;color:#6b7280;">
                                ✘ CANCELLED
                            </div>
                        @elseif($checkerApproved)
                            <div class="approval-stamp" style="border-color:#16a34a;color:#16a34a;">
                                ✔ APPROVED
                            </div>
                        @elseif($rejectorIsChecker)
                            <div class="approval-stamp" style="border-color:#dc2626;color:#dc2626;">
                                ✘ REJECTED
                            </div>
                        @else
                            <div class="approval-stamp" style="border-color:#d97706;color:#d97706;">
                                ⏳ WAITING
                            </div>
                        @endif
                    </div>

                    @if($checkerNotRequired)
                        <div class="signature-name">
                            &nbsp;
                            <div class="signature-title">(Not required)</div>
                        </div>
                    @elseif($isCancelled)
                        <div class="signature-name">
                            {{ $cuti->cancelledBy?->name ?? '-' }}
                            <div class="signature-title">
                                {{ $cuti->cancelled_at?->format('d F Y, H:i') }} WIB
                            </div>
                        </div>
                    @elseif($checker)
                        <div class="signature-name">
                            {{ $checker->name }}
                            <div class="signature-title">
                                ({{ $checker->jabatan ?? 'Manager' }})
                                @if($checkerApproved && $checkerApprovedAt)
                                    <br>{{ $checkerApprovedAt->format('d F Y, H:i') }} WIB
                                @endif
                            </div>
                        </div>
                    @elseif($rejectorIsChecker)
                        <div class="signature-name">
                            {{ $cuti->rejector?->name ?? '-' }}
                            <div class="signature-title">
                                ({{ $cuti->rejector?->jabatan ?? 'Manager' }})<br>
                                {{ $cuti->rejected_at?->format('d F Y, H:i') }} WIB
                            </div>
                        </div>
                    @else
                        <div class="signature-name">&nbsp;</div>
                    @endif
                </td>

                {{-- KOLOM 3: approval final HRD, kecuali pemohon HRD --}}
                <td>
                    Disetujui oleh / <em>Approved by</em>

                    <div class="signature-space">
                        @if($isHrdApplicant)
                            <div class="approval-stamp" style="border-color:#9ca3af;color:#9ca3af;">
                                N/A
                            </div>
                        @elseif($isCancelled)
                            <div class="approval-stamp" style="border-color:#6b7280;color:#6b7280;">
                                ✘ CANCELLED
                            </div>
                        @elseif($cuti->isApproved() && $cuti->approved_by_hrd)
                            <div class="approval-stamp" style="border-color:#16a34a;color:#16a34a;">
                                ✔ APPROVED
                            </div>
                        @elseif($rejectorIsHrd)
                            <div class="approval-stamp" style="border-color:#dc2626;color:#dc2626;">
                                ✘ REJECTED
                            </div>
                        @elseif($checkerApproved || $checkerNotRequired)
                            <div class="approval-stamp" style="border-color:#d97706;color:#d97706;">
                                ⏳ WAITING
                            </div>
                        @else
                            <div class="approval-stamp" style="border-color:#9ca3af;color:#9ca3af;">
                                — —
                            </div>
                        @endif
                    </div>

                    @if($isHrdApplicant)
                        <div class="signature-name">&nbsp;
                            <div class="signature-title">(Not required)</div>
                        </div>
                    @elseif($isCancelled)
                        <div class="signature-name">&nbsp;
                            <div class="signature-title">(Human Resource Department)</div>
                        </div>
                    @elseif($cuti->hrd && $cuti->isApproved())
                        <div class="signature-name">
                            {{ $cuti->hrd->name }}
                            <div class="signature-title">
                                ({{ $cuti->hrd->jabatan ?? 'HRD' }})<br>
                                {{ $cuti->approved_hrd_at?->format('d F Y, H:i') }} WIB
                            </div>
                        </div>
                    @elseif($rejectorIsHrd)
                        <div class="signature-name">
                            {{ $cuti->rejector?->name ?? '-' }}
                            <div class="signature-title">
                                ({{ $cuti->rejector?->jabatan ?? 'HRD' }})<br>
                                {{ $cuti->rejected_at?->format('d F Y, H:i') }} WIB
                            </div>
                        </div>
                    @else
                        <div class="signature-name">&nbsp;
                            <div class="signature-title">(Human Resource Department)</div>
                        </div>
                    @endif
                </td>

            </tr>
        </table>
    </div>
</body>
</html>
