<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meal Claim</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 9px; margin: 0; padding: 25px 32px; }
        h1, p { margin: 0; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h1 { color: #173f72; font-size: 17px; letter-spacing: 2px; }
        .header p { margin-top: 3px; color: #6b7280; font-size: 9px; letter-spacing: 1px; }
        .meta { width: 100%; margin-bottom: 8px; }
        .meta td { border: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 6px 7px; vertical-align: middle; }
        th { background: #e9eef5; font-weight: bold; text-align: center; }
        .label { background: #f8fafc; font-weight: bold; width: 20%; }
        .amount { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .total td { background: #eff6ff; font-size: 10px; font-weight: bold; }
        .note { white-space: pre-line; min-height: 34px; }
        .section-title { color: #173f72; font-size: 10px; font-weight: bold; letter-spacing: .8px; margin: 15px 0 6px; }
        .signatures td { width: 25%; text-align: center; vertical-align: top; padding: 7px; }
        .signature-space { height: 61px; position: relative; }
        .stamp { position: absolute; top: 22px; left: 50%; transform: translateX(-50%) rotate(-13deg); border: 2px solid; border-radius: 5px; padding: 4px 7px; font-weight: bold; font-size: 10px; letter-spacing: 1px; white-space: nowrap; }
        .signature-name { border-top: 1px solid #6b7280; font-weight: bold; min-height: 18px; padding-top: 3px; }
        .signature-role, .signature-date { color: #6b7280; font-size: 8px; margin-top: 2px; }
        .status { border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; text-align: center; padding: 7px; font-weight: bold; margin-top: 10px; }
        .rejected { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
        .footer { color: #6b7280; font-size: 7.5px; text-align: center; margin-top: 12px; }
    </style>
</head>
<body>
    @php
        $isRejected = $record->status === 'Rejected';
        $isCancelled = $record->status === 'Cancelled';
        $statusColor = $isRejected ? '#dc2626' : ($isCancelled ? '#6b7280' : '#d97706');
        $statusLabel = $isRejected ? 'REJECTED' : ($isCancelled ? 'CANCELLED' : 'PENDING');
        $submittedDepartment = $record->user?->jabatan === 'Staff'
            ? $record->department?->nama_department
            : $record->user?->jabatan;
    @endphp

    <div class="header">
        <h1>MEAL CLAIM</h1>
        <p>MEAL EXPENSE REIMBURSEMENT FORM</p>
    </div>

    <table class="meta">
        <tr>
            <td><strong>PT: {{ $record->company?->kode ?? '-' }}</strong></td>
            <td style="text-align:right;"><strong>Claim No.:</strong> MC-{{ str_pad((string) $record->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="label">Employee / Name</td>
            <td>{{ $record->user?->name ?? '-' }}</td>
            <td class="label">Claim Date</td>
            <td>{{ $record->claim_date?->format('d M Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Department</td>
            <td>{{ $record->department?->nama_department ?? '-' }}</td>
            <td class="label">Receipt Count</td>
            <td>{{ $record->receipt_count }}</td>
        </tr>
    </table>

    <div class="section-title">MEAL EXPENSE DETAILS</div>
    <table>
        <thead>
            <tr>
                <th style="width:6%;">No.</th>
                <th style="width:16%;">Date</th>
                <th style="width:17%;">Meal Type</th>
                <th>Merchant / Description</th>
                <th style="width:18%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($record->items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $item->meal_date?->format('d M Y') }}</td>
                    <td>{{ $item->meal_type }}</td>
                    <td>{{ $item->merchant ?: ($item->note ?: '-') }}</td>
                    <td class="amount">IDR {{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                </tr>
                @if(filled($item->note) && filled($item->merchant))
                    <tr><td></td><td colspan="4" style="font-size:8px; color:#4b5563;"><em>Note: {{ $item->note }}</em></td></tr>
                @endif
            @empty
                <tr><td colspan="5" class="center" style="color:#6b7280;">No meal expense details.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="4" style="text-align:right;">TOTAL AMOUNT</td>
                <td class="amount">IDR {{ number_format((float) $record->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top:-1px;">
        <tr>
            <td class="label">Employee Note</td>
            <td class="note">{{ $record->employee_note ?: '-' }}</td>
        </tr>
        @if(filled($record->verification_note))
            <tr>
                <td class="label">Verification Note</td>
                <td class="note">{{ $record->verification_note }}</td>
            </tr>
        @endif
        @if(filled($record->payment_note))
            <tr>
                <td class="label">Payment Reference</td>
                <td class="note">{{ $record->payment_note }}</td>
            </tr>
        @endif
        @if($isRejected)
            <tr>
                <td class="label" style="color:#b91c1c;">Rejection Reason</td>
                <td class="note" style="color:#b91c1c;">{{ $record->rejected_note ?: '-' }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">CLAIM PROCESS</div>
    <table class="signatures">
        <tr>
            <td>
                <div>Submitted by</div>
                <div class="signature-space"><div class="stamp" style="color:#b45309; border-color:#b45309;">SUBMITTED</div></div>
                <div class="signature-name">{{ $record->user?->name ?? '-' }}</div>
                <div class="signature-role">{{ $submittedDepartment ?: '-' }}</div>
                <div class="signature-date">{{ $record->created_at?->format('d M Y, H:i') }} WIB</div>
            </td>
            <td>
                <div>Verified by</div>
                <div class="signature-space">
                    @if($record->verified_at)
                        <div class="stamp" style="color:#2563eb; border-color:#2563eb;">VERIFIED</div>
                    @else
                        <div class="stamp" style="color:{{ $statusColor }}; border-color:{{ $statusColor }};">{{ $statusLabel }}</div>
                    @endif
                </div>
                <div class="signature-name">{{ $record->verifier?->name ?? '-' }}</div>
                <div class="signature-role">HRD / Finance Operations</div>
                @if($record->verified_at)<div class="signature-date">{{ $record->verified_at->format('d M Y, H:i') }} WIB</div>@endif
            </td>
            <td>
                <div>Approved by</div>
                <div class="signature-space">
                    @if($record->approved_at)
                        <div class="stamp" style="color:#16a34a; border-color:#16a34a;">APPROVED</div>
                    @else
                        <div class="stamp" style="color:{{ $statusColor }}; border-color:{{ $statusColor }};">{{ $statusLabel }}</div>
                    @endif
                </div>
                <div class="signature-name">{{ $record->approver?->name ?? '-' }}</div>
                <div class="signature-role">Finance Manager</div>
                @if($record->approved_at)<div class="signature-date">{{ $record->approved_at->format('d M Y, H:i') }} WIB</div>@endif
            </td>
            <td>
                <div>Paid by</div>
                <div class="signature-space">
                    @if($record->paid_at)
                        <div class="stamp" style="color:#15803d; border-color:#15803d;">PAID</div>
                    @else
                        <div class="stamp" style="color:{{ $statusColor }}; border-color:{{ $statusColor }};">{{ $statusLabel }}</div>
                    @endif
                </div>
                <div class="signature-name">{{ $record->payer?->name ?? '-' }}</div>
                <div class="signature-role">Finance, Accounting &amp; Tax Department</div>
                @if($record->paid_at)<div class="signature-date">{{ $record->paid_at->format('d M Y, H:i') }} WIB</div>@endif
            </td>
        </tr>
    </table>

    <div class="status {{ $isRejected ? 'rejected' : '' }}">CURRENT STATUS: {{ strtoupper($record->status) }}</div>
    <div class="footer">Internal System - Generated on {{ now()->format('d M Y, H:i') }} WIB</div>
</body>
</html>
