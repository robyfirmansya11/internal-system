<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Reimbursement Note</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; margin: 0; padding: 30px 38px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 17px; letter-spacing: 2px; margin: 0; }
        .header p { margin: 3px 0 0; font-size: 10px; letter-spacing: 1.5px; color: #4b5563; }
        .topline { width: 100%; margin-bottom: 8px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #6b7280; padding: 7px 8px; vertical-align: middle; }
        th { background: #eef1f5; text-align: center; font-weight: bold; }
        .description { height: 28px; vertical-align: top; white-space: pre-line; }
        .amount { text-align: right; font-weight: bold; }
        .total-label { text-align: right; background: #f8fafc; font-weight: bold; }
        .label { width: 29%; background: #f8fafc; font-weight: bold; }
        .muted { color: #4b5563; font-size: 8.5px; }
        .signatures { margin-top: 18px; }
        .signatures td { width: 20%; text-align: center; vertical-align: top; padding: 7px; }
        .signature-space { height: 68px; position: relative; }
        .stamp { position: absolute; top: 25px; left: 50%; transform: translateX(-50%) rotate(-14deg); border: 2px solid; border-radius: 5px; padding: 3px 8px; font-weight: bold; letter-spacing: 1px; white-space: nowrap; }
        .name { border-top: 1px solid #6b7280; padding-top: 3px; font-weight: bold; min-height: 17px; }
        .role { margin-top: 2px; font-size: 8.5px; color: #4b5563; }
        .date { margin-top: 2px; font-size: 8px; color: #4b5563; }
        .notice { margin-top: 10px; padding: 7px; border: 1px solid #fecaca; background: #fef2f2; color: #b91c1c; text-align: center; }
        .footer { margin-top: 10px; color: #6b7280; text-align: center; font-size: 8px; }
    </style>
</head>
<body>
    @php
        $submittedRole = in_array($record->user?->jabatan, ['Manager', 'Finance Manager'], true)
            ? $record->user?->jabatan
            : $record->department?->nama_department;
        $atasan = $record->user?->profile?->atasan;
        $managerApproved = (bool) $record->approved_by_manager;
        $financeApproved = $record->isApproved() && (bool) $record->approved_by;
    @endphp

    <div class="header">
        <h1>NOTA PENGGANTIAN BIAYA</h1>
        <p>EXPENSE REIMBURSEMENT NOTE</p>
    </div>

    <table class="topline">
        <tr>
            <td style="border:0; padding:0;"><strong>PT: {{ $record->company?->kode }}</strong></td>
            <td style="border:0; padding:0; text-align:right;">D {{ $record->tanggal?->format('d') }} &nbsp; M {{ $record->tanggal?->format('m') }} &nbsp; Y {{ $record->tanggal?->format('Y') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:72%;">Keterangan / Description</th>
                <th>Jumlah / Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($record->details->take(5) as $detail)
                <tr>
                    <td class="description">{{ $detail->keterangan }}</td>
                    <td class="amount">Rp {{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @for($row = $record->details->count(); $row < 5; $row++)
                <tr>
                    <td class="description">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
            <tr>
                <td class="total-label">Jumlah Total / Total Amount</td>
                <td class="amount">Rp {{ number_format($record->jumlah_total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top:-1px;">
        <tr>
            <td class="label">Terbilang / In Words</td>
            <td><em>{{ $record->terbilang }}</em></td>
        </tr>
        <tr>
            <td class="label">Informasi Transfer Dana<br><span class="muted">Fund Transfer Information</span></td>
            <td style="white-space:pre-line;">{{ $record->informasi_transfer }}</td>
            <td class="label" style="width:19%;">Jumlah Lampiran<br><span class="muted">Total Attachment</span></td>
            <td style="width:11%; text-align:center;">{{ $record->jumlah_lampiran }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div>Diajukan oleh /<br><em>Submitted by</em></div>
                <div class="signature-space"><div class="stamp" style="color:#b45309; border-color:#b45309;">SUBMITTED</div></div>
                <div class="name">{{ $record->user?->name }}</div>
                <div class="role">{{ $submittedRole }}</div>
            </td>
            <td>
                <div>Diperiksa oleh /<br><em>Checked by</em></div>
                <div class="signature-space">
                    @if($record->isRejected() && ! $managerApproved)
                        <div class="stamp" style="color:#dc2626; border-color:#dc2626;">REJECTED</div>
                    @elseif($managerApproved || ($financeApproved && $record->user?->jabatan === 'Manager'))
                        <div class="stamp" style="color:#2563eb; border-color:#2563eb;">CHECKED</div>
                    @elseif($record->isCancelled())
                        <div class="stamp" style="color:#6b7280; border-color:#6b7280;">CANCELLED</div>
                    @else
                        <div class="stamp" style="color:#d97706; border-color:#d97706;">PENDING</div>
                    @endif
                </div>
                <div class="name">{{ $record->approverManager?->name ?? $atasan?->name }}</div>
                <div class="role">{{ $record->approverManager?->jabatan ?? $atasan?->jabatan ?? 'Manager' }}</div>
                @if($record->approved_manager_at)<div class="date">{{ $record->approved_manager_at->format('d F Y, H:i') }} WIB</div>@endif
            </td>
            <td>
                <div>Disetujui oleh /<br><em>Approved by</em></div>
                <div class="signature-space">
                    @if($financeApproved)<div class="stamp" style="color:#16a34a; border-color:#16a34a;">APPROVED</div>
                    @elseif($record->isRejected())<div class="stamp" style="color:#dc2626; border-color:#dc2626;">REJECTED</div>
                    @elseif($record->isCancelled())<div class="stamp" style="color:#6b7280; border-color:#6b7280;">CANCELLED</div>
                    @else<div class="stamp" style="color:#d97706; border-color:#d97706;">PENDING</div>@endif
                </div>
                <div class="name">{{ $record->approver?->name }}</div>
                <div class="role">Finance Manager</div>
                @if($record->approved_at)<div class="date">{{ $record->approved_at->format('d F Y, H:i') }} WIB</div>@endif
            </td>
            <td><div>Disetujui oleh /<br><em>Approved by</em></div><div class="signature-space"></div><div class="name">&nbsp;</div><div class="role">Vice President</div></td>
            <td><div>Disetujui oleh /<br><em>Approved by</em></div><div class="signature-space"></div><div class="name">&nbsp;</div><div class="role">President Director</div></td>
        </tr>
    </table>

    @if($record->isRejected())
        <div class="notice">REJECTED by {{ $record->rejector?->name }} - {{ $record->rejected_note }}</div>
    @endif

    <div class="footer">Internal System - Generated on {{ now()->format('d F Y H:i') }}</div>
</body>
</html>
