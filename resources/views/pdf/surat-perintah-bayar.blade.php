<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPB - Payment Application Form</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #000;
            padding: 20px 30px;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 16px; font-weight: bold; letter-spacing: 2px; }
        .header h2 { font-size: 11px; font-weight: normal; letter-spacing: 1px; }
        .pt-label { font-size: 11px; margin-bottom: 5px; }
        .date-table { width: 100%; margin-bottom: 8px; }
        .date-table td { padding: 2px 0; }
        .date-table .label { width: 60%; }
        .date-table .value {
            width: 40%; border: 1px solid #000;
            padding: 2px 6px; text-align: center;
        }
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .main-table th, .main-table td {
            border: 1px solid #000; padding: 5px 7px; vertical-align: top;
        }
        .main-table th {
            text-align: center; font-size: 10px; background-color: #f0f0f0;
        }
        .w-full { width: 100%; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .signature-table td {
            border: 1px solid #000; text-align: center;
            padding: 5px; width: 20%; vertical-align: top;
        }
        .signature-space { height: 60px; position: relative; }
        .stamp {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            border: 3px solid; border-radius: 6px;
            padding: 3px 10px; font-size: 12px; font-weight: bold;
            letter-spacing: 2px; opacity: 0.6; white-space: nowrap;
        }
        .sig-name {
            font-size: 10px; border-top: 1px solid #000;
            padding-top: 3px; margin-top: 3px;
        }
        .sig-sub { font-size: 9px; color: #555; margin-top: 2px; }
        .footer-note {
            font-size: 9px; margin-top: 10px;
            text-align: center; color: #555;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
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
                <td rowspan="5">{{ $record->company->nama ?? '' }}</td>
                <td>Rp {{ number_format($record->jumlah, 0, ',', '.') }}</td>
                <td rowspan="5" class="text-center">{{ $record->pembayaran_tahap }}</td>
                <td rowspan="5" class="text-center">{{ $record->jumlah_lampiran }}</td>
            </tr>
            <tr><td>PPN &nbsp;&nbsp; Rp {{ number_format($record->ppn, 0, ',', '.') }}</td></tr>
            <tr><td>PPH &nbsp;&nbsp; Rp {{ number_format($record->pph, 0, ',', '.') }} (-)</td></tr>
            <tr><td>Admin &nbsp; Rp {{ number_format($record->admin, 0, ',', '.') }}</td></tr>
            <tr><td></td></tr>
        </tbody>
    </table>

    {{-- TOTAL --}}
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="width:35%; border:1px solid #000; padding:6px 7px; font-weight:bold; font-size:10px;">
                Jumlah Total Yang Ditagihkan /<br>Total Amount Billed
            </td>
            <td style="width:20%; border:1px solid #000; padding:6px 7px; font-weight:bold;">
                Rp. {{ number_format($record->jumlah_total, 0, ',', '.') }}
            </td>
            <td style="border:1px solid #000; padding:6px 7px;">
                <span style="font-weight:bold; font-size:10px;">Terbilang / In Words:</span><br>
                <span style="font-style:italic;">{{ $record->terbilang }}</span>
            </td>
        </tr>
    </table>

    {{-- KETERANGAN --}}
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="border:1px solid #000; padding:6px 7px;">
                <div style="font-size:10px; color:#555;">Keterangan / Description</div>
                <div>{{ $record->keterangan }}</div>
            </td>
        </tr>
    </table>

    {{-- INFORMASI TRANSFER --}}
    <table class="w-full" style="border-collapse:collapse; margin-top:-1px;">
        <tr>
            <td style="border:1px solid #000; padding:6px 7px;">
                <div style="font-size:10px; color:#555;">Informasi Transfer Dana / Fund Transfer Information</div>
                <div>{{ $record->informasi_transfer }}</div>
            </td>
        </tr>
    </table>

    {{-- SIGNATURE --}}
    <table class="signature-table">
        <tr>

            {{-- KOLOM 1: PEMOHON --}}
            <td>
                <div style="font-size:10px;">Diajukan oleh /<br><em>Submitted by</em> :</div>
                <div class="signature-space">
                    {{-- Selalu SUBMITTED karena record sudah ada --}}
                    <div class="stamp" style="border-color:#ff9100; color:#ff9100;">
                        ✔ SUBMITTED
                    </div>
                </div>
                <div class="sig-name">
                    {{ $record->user?->name ?? '' }}
                </div>
                <div class="sig-sub">
                    {{ $record->department?->nama_department ?? '' }}
                </div>
            </td>

            {{-- KOLOM 2: ATASAN (approved_by_manager) --}}
            <td>
                <div style="font-size:10px;">Diperiksa oleh /<br><em>Checked by</em> :</div>
                <div class="signature-space">
                    @if($record->approved_by_manager)
                        {{-- Atasan sudah approve --}}
                        <div class="stamp" style="border-color:#2563eb; color:#2563eb;">
                            ✔ APPROVED
                        </div>
                    @elseif($record->isRejected())
                        <div class="stamp" style="border-color:#dc2626; color:#dc2626;">
                            ✘ REJECTED
                        </div>
                    @else
                        <div class="stamp" style="border-color:#d97706; color:#d97706;">
                            ⏳ PENDING
                        </div>
                    @endif
                </div>
                @if($record->approverManager)
                    <div class="sig-name">
                        {{ $record->approverManager->name }}
                    </div>
                    <div class="sig-sub">
                      {{ $record->department?->nama_department }}
                    </div>
                    <div class="sig-sub">
                        {{ $record->approved_manager_at?->format('d F Y, H:i') }} WIB
                    </div>
                @else
                    <div class="sig-name">&nbsp;</div>
                @endif
            </td>

            {{-- KOLOM 3: FINANCE MANAGER (approved_by) --}}
            <td>
                <div style="font-size:10px;">Disetujui oleh /<br><em>Approved by</em> :</div>
                <div class="signature-space">
                    @if($record->isApproved() && $record->approved_by)
                        <div class="stamp" style="border-color:#16a34a; color:#16a34a;">
                            ✔ APPROVED
                        </div>
                    @elseif($record->isRejected())
                        <div class="stamp" style="border-color:#dc2626; color:#dc2626;">
                            ✘ REJECTED
                        </div>
                    @else
                        <div class="stamp" style="border-color:#d97706; color:#d97706;">
                            ⏳ PENDING
                        </div>
                    @endif
                </div>
                @if($record->approver && $record->isApproved())
                    <div class="sig-name">
                        {{ $record->approver->name }}
                    </div>
                    <div class="sig-sub">Finance Manager</div>
                    <div class="sig-sub">
                        {{ $record->approved_at?->format('d F Y, H:i') }} WIB
                    </div>
                @else
                    <div class="sig-name">&nbsp;</div>
                    <div class="sig-sub">Finance Manager</div>
                @endif
            </td>

            {{-- KOLOM 4: VICE PRESIDENT --}}
            <td>
                <div style="font-size:10px;">Disetujui oleh /<br><em>Approved by</em> :</div>
                <div class="signature-space"></div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-sub">Vice President</div>
            </td>

            {{-- KOLOM 5: PRESIDENT DIRECTOR --}}
            <td>
                <div style="font-size:10px;">Disetujui oleh /<br><em>Approved by</em> :</div>
                <div class="signature-space"></div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-sub">President Director</div>
            </td>

        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer-note">
        Internal System — Generated on {{ now()->format('d F Y H:i') }}
    </div>

</body>
</html>
