<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPB - Payment Application Form</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            padding: 16px 26px;
        }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 {
            font-size: 15px; font-weight: bold; letter-spacing: 2px;
            color: #1e3a5f;
        }
        .header h2 {
            font-size: 10px; font-weight: normal; letter-spacing: 1px;
            color: #666;
        }
        .pt-label { font-size: 10px; margin-bottom: 4px; }
        .pt-label b { color: #1e3a5f; }

        .date-table { width: 100%; margin-bottom: 6px; }
        .date-table td { padding: 1px 0; }
        .date-table .label { width: 60%; }
        .date-table .value {
            width: 40%; border: 1px solid #1e3a5f;
            padding: 2px 6px; text-align: center;
            font-weight: bold; color: #1e3a5f;
        }

        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .main-table th, .main-table td {
            border: 1px solid #999; padding: 4px 7px; vertical-align: top;
        }
        .main-table th {
            text-align: center; font-size: 9px; background-color: #f0f0f0;
            color: #444;
        }

        .w-full { width: 100%; }

        .signature-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .signature-table td {
            border: 1px solid #999; text-align: center;
            padding: 4px; width: 20%; vertical-align: top;
        }
        .signature-space { height: 44px; position: relative; }
        .stamp {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            border: 2.5px solid; border-radius: 5px;
            padding: 2px 8px; font-size: 10px; font-weight: bold;
            letter-spacing: 1.5px; opacity: 0.75; white-space: nowrap;
        }
        .sig-name {
            font-size: 9.5px; border-top: 1px solid #999;
            padding-top: 2px; margin-top: 2px; font-weight: bold;
        }
        .sig-sub { font-size: 8.5px; color: #666; margin-top: 1px; }
        .footer-note {
            font-size: 8px; margin-top: 6px;
            text-align: center; color: #888;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .paid-banner {
            margin-top: 8px;
            padding: 7px 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            text-align: center;
        }
        .paid-banner .badge {
            display: inline-block;
            border: 1.5px solid #dc2626;
            border-radius: 4px;
            padding: 2px 10px;
            color: #dc2626;
            font-weight: bold;
            font-size: 10px;
            letter-spacing: 1px;
            margin-right: 6px;
        }
        .paid-banner .info {
            color: #dc2626;
            font-size: 9.5px;
        }
    </style>
</head>
<body>

    {{-- PT --}}
    <div class="pt-label">PT : <b>{{ $record->company->kode ?? '' }}</b></div>

    {{-- HEADER --}}
    <div class="header">
        <h1>SURAT PERINTAH BAYAR</h1>
        <h2>PAYMENT APPLICATION FORM</h2>
    </div>

    {{-- TANGGAL --}}
    <table class="date-table">
        <tr>
            <td class="label">Tanggal Penagihan / Date of Application</td>
            <td class="value">{{ $record->tanggal_penagihan?->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Jatuh Tempo / Due Date</td>
            <td class="value">{{ $record->tanggal_jatuhtempo?->format('d F Y') }}</td>
        </tr>
    </table>

    {{-- TABEL INVOICE --}}
    <table class="main-table">
        <thead>
            <tr>
                <th style="width:15%">No. Invoice /<br>Invoice No.</th>
                <th style="width:25%">Nama Perusahaan /<br>Company Name</th>
                <th style="width:20%">Jumlah / Amount</th>
                <th style="width:20%">Pembayaran Tahap Ke /<br>Stage of Payment</th>
                <th style="width:20%">Jumlah Lampiran /<br>Total Attachment</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="5" class="text-center">{{ $record->no_invoice }}</td>
                <td rowspan="5" class="text-center">{{ $record->customer }}</td>
                <td>Rp {{ number_format($record->jumlah, 0, ',', '.') }}</td>
                <td rowspan="5" class="text-center">{{ $record->pembayaran_tahap }}</td>
                <td rowspan="5" class="text-center">{{ $record->jumlah_lampiran }}</td>
            </tr>
            <tr><td>PPN &nbsp;&nbsp; Rp {{ number_format($record->ppn, 0, ',', '.') }}</td></tr>
            <tr><td>PPH &nbsp;&nbsp; Rp {{ number_format($record->pph, 0, ',', '.') }} (-)</td></tr>
            <tr><td>Admin &nbsp; Rp {{ number_format($record->admin, 0, ',', '.') }}</td></tr>
            <tr><td>&nbsp;</td></tr>
        </tbody>
    </table>

    {{-- TOTAL --}}
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="width:35%; border:1px solid #999; padding:5px 7px; font-weight:bold; font-size:9.5px; background:#f7f9fb; color:#1e3a5f;">
                Jumlah Total Yang Ditagihkan /<br>Total Amount Billed
            </td>
            <td style="width:20%; border:1px solid #999; padding:5px 7px; font-weight:bold; color:#1e3a5f;">
                Rp. {{ number_format($record->jumlah_total, 0, ',', '.') }}
            </td>
            <td style="border:1px solid #999; padding:5px 7px;">
                <span style="font-weight:bold; font-size:9.5px; color:#555;">Terbilang / In Words:</span><br>
                <span style="font-style:italic;">{{ $record->terbilang }}</span>
            </td>
        </tr>
    </table>

    {{-- KETERANGAN --}}
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="border:1px solid #999; padding:5px 7px;">
                <div style="font-size:9px; color:#777;">Keterangan / Description</div>
                <div>{{ $record->keterangan }}</div>
            </td>
        </tr>
    </table>

    {{-- INFORMASI TRANSFER --}}
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="border:1px solid #999; padding:5px 7px;">
                <div style="font-size:9px; color:#777;">Informasi Transfer Dana / Fund Transfer Information</div>
                <div>{{ $record->informasi_transfer }}</div>
            </td>
        </tr>
    </table>

    @if($record->isRejected() && $record->rejected_note)
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="border:1px solid #999; padding:5px 7px; color:#dc2626;">
                <div style="font-size:9px;">Catatan Penolakan / Rejected Note</div>
                <div>{{ $record->rejected_note }}</div>
            </td>
        </tr>
    </table>
    @endif

    @php
        $isCancelled = $record->isCancelled();
        $isPaid      = $record->isPaid();

        // Tentukan siapa yang menolak berdasarkan jabatan rejector,
        // karena approval_level sudah ditimpa jadi -1 saat reject
        // sehingga tidak bisa dipakai lagi untuk menentukan level penolakan.
        $rejectorIsLevel2 = $record->isRejected()
            && $record->rejector
            && $record->rejector->jabatan === \App\Models\SuratPerintahBayar::LEVEL2_JABATAN;

        $rejectorIsAtasan = $record->isRejected() && ! $rejectorIsLevel2;

        // Atasan ditentukan dari profile pemohon (mengikuti department pemohon,
        // bukan department milik atasan sendiri — karena satu manager
        // bisa membawahi banyak department).
        $atasanPemohon = $record->user?->profile?->atasan;
    @endphp

    {{-- SIGNATURE --}}
    <table class="signature-table">
        <tr>

            {{-- KOLOM 1: PEMOHON --}}
            <td>
                <div style="font-size:9.5px;">Diajukan oleh /<br><em>Submitted by</em> :</div>
                <div class="signature-space">
                    <div class="stamp" style="border-color:#b45309; color:#b45309;">
                        &#10003; SUBMITTED
                    </div>
                </div>
                <div class="sig-name">
                    {{ $record->user?->name ?? '' }}
                </div>
                <div class="sig-sub">
                    {{ $record->department?->nama_department ?? '' }}
                </div>
            </td>

            {{-- KOLOM 2: ATASAN --}}
            <td>
                <div style="font-size:9.5px;">Diperiksa oleh /<br><em>Checked by</em> :</div>
                <div class="signature-space">
                    @if($isCancelled)
                        <div class="stamp" style="border-color:#6b7280; color:#6b7280;">
                            &#10007; CANCELLED
                        </div>
                    @elseif($record->approved_by_manager)
                        <div class="stamp" style="border-color:#2563eb; color:#2563eb;">
                            &#10003; CHECKED
                        </div>
                    @elseif($rejectorIsAtasan)
                        <div class="stamp" style="border-color:#dc2626; color:#dc2626;">
                            &#10007; REJECTED
                        </div>
                    @else
                        <div class="stamp" style="border-color:#d97706; color:#d97706;">
                            PENDING
                        </div>
                    @endif
                </div>
                @if($isCancelled)
                    <div class="sig-name">
                        {{ $record->cancelledBy?->name ?? '-' }}
                    </div>
                    <div class="sig-sub">
                        {{ $record->cancelled_at?->format('d F Y, H:i') }} WIB
                    </div>
                @elseif($atasanPemohon)
                    <div class="sig-name">
                        {{ $atasanPemohon->name }}
                    </div>
                    <div class="sig-sub">
                        {{ $record->department?->nama_department }}
                    </div>
                    @if($record->approved_by_manager && $record->approved_manager_at)
                        <div class="sig-sub">
                            {{ $record->approved_manager_at->format('d F Y, H:i') }} WIB
                        </div>
                    @endif
                @elseif($rejectorIsAtasan)
                    <div class="sig-name">
                        {{ $record->rejector?->name ?? '-' }}
                    </div>
                    <div class="sig-sub">
                        {{ $record->department?->nama_department }}
                    </div>
                    <div class="sig-sub">
                        {{ $record->rejected_at?->format('d F Y, H:i') }} WIB
                    </div>
                @else
                    <div class="sig-name">&nbsp;</div>
                @endif
            </td>

            {{-- KOLOM 3: FINANCE MANAGER --}}
            <td>
                <div style="font-size:9.5px;">Disetujui oleh /<br><em>Approved by</em> :</div>
                <div class="signature-space">
                    @if($isCancelled)
                        <div class="stamp" style="border-color:#6b7280; color:#6b7280;">
                            &#10007; CANCELLED
                        </div>
                    @elseif($isPaid || ($record->isApproved() && $record->approved_by))
                        <div class="stamp" style="border-color:#16a34a; color:#16a34a;">
                            &#10003; APPROVED
                        </div>
                    @elseif($record->isRejected())
                        <div class="stamp" style="border-color:#dc2626; color:#dc2626;">
                            &#10007; REJECTED
                        </div>
                    @else
                        <div class="stamp" style="border-color:#d97706; color:#d97706;">
                            PENDING
                        </div>
                    @endif
                </div>
                @if($isCancelled)
                    <div class="sig-name">&nbsp;</div>
                    <div class="sig-sub">Finance Manager</div>
                @elseif($record->approver && ($record->isApproved() || $isPaid))
                    <div class="sig-name">
                        {{ $record->approver->name }}
                    </div>
                    <div class="sig-sub">Finance Manager</div>
                    <div class="sig-sub">
                        {{ $record->approved_at?->format('d F Y, H:i') }} WIB
                    </div>
                @elseif($rejectorIsLevel2)
                    <div class="sig-name">
                        {{ $record->rejector?->name ?? '-' }}
                    </div>
                    <div class="sig-sub">Finance Manager</div>
                    <div class="sig-sub">
                        {{ $record->rejected_at?->format('d F Y, H:i') }} WIB
                    </div>
                @else
                    <div class="sig-name">&nbsp;</div>
                    <div class="sig-sub">Finance Manager</div>
                @endif
            </td>

            {{-- KOLOM 4: VICE PRESIDENT --}}
            <td>
                <div style="font-size:9.5px;">Disetujui oleh /<br><em>Approved by</em> :</div>
                <div class="signature-space"></div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-sub">Vice President</div>
            </td>

            {{-- KOLOM 5: PRESIDENT DIRECTOR --}}
            <td>
                <div style="font-size:9.5px;">Disetujui oleh /<br><em>Approved by</em> :</div>
                <div class="signature-space"></div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-sub">President Director</div>
            </td>

        </tr>
    </table>

    {{-- BANNER PAID — muncul di bawah kotak persetujuan --}}
    @if($isPaid)
    <div class="paid-banner">
        <span class="badge">&#10003; PAID</span>
        <span class="info">
            Dibayarkan oleh <strong>{{ $record->paidBy?->name ?? '-' }}</strong>
            pada {{ $record->paid_at?->format('d F Y, H:i') }} WIB
        </span>
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer-note">
        Internal System — Generated on {{ now()->format('d F Y H:i') }}
    </div>

</body>
</html>
