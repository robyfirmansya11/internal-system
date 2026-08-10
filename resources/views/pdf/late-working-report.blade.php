<!DOCTYPE html>
<html>
<head>
    <title>Late Working Permit Report</title>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11px;
    background: #ffffff;
    color: #1a1a2e;
    padding: 32px 36px;
  }

  /* ── HEADER ── */
 .header {
        /* flex: 0 0 auto;
    text-align: center;
    padding: 0 24px; */
    display: flex;
    justify-content: space-between;
    align-items: center;        /* <-- center biar logo sejajar vertikal */
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 2.5px solid #1a1a2e;
}

  .header-left .company-name {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: #1a1a2e;
  }

  .header-left .company-sub {
    font-size: 10px;
    color: #6b7280;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .header-left  { flex: 1;
    text-align: left; }

.header-right { flex: 1;
    text-align: right; }

.header-center {
    flex: 0 0 auto;
    text-align: center;
    padding: 0 12px;
}
.header-center img {
       height: 55px;
    width: auto;
    display: block;
    margin: 0 auto 6px;
    object-fit: contain;
}

  .header-right .doc-title {
    font-size: 15px;
    font-weight: 700;
    color: #1a1a2e;
    text-transform: uppercase;
    letter-spacing: 1.5px;
  }

  .header-right .doc-meta {
    font-size: 9.5px;
    color: #6b7280;
    margin-top: 4px;
  }

  .accent-bar {
    height: 4px;
    background: linear-gradient(90deg, #1a1a2e 0%, #4f46e5 60%, #818cf8 100%);
    border-radius: 2px;
    margin-bottom: 24px;
  }

  .company-divider {
    width: 80%;
    margin: 0 auto 5px;
    border-top: 1px solid #d1d5db;
}

.company-full-name {
    font-size: 10px;
    font-weight: 600;
    color: #374151;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    white-space: nowrap;
}

  /* ── SUMMARY PILLS ── */
  .summary-row {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
  }

  .pill {
    flex: 1;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
  }

  .pill-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #9ca3af;
    margin-bottom: 3px;
  }

  .pill-value {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a2e;
  }

  .pill.total   { background: #f0f4ff; border-color: #c7d2fe; }
  .pill.approved { background: #f0fdf4; border-color: #bbf7d0; }
  .pill.approved .pill-value { color: #15803d; }
  .pill.pending { background: #fffbeb; border-color: #fde68a; }
  .pill.pending .pill-value  { color: #b45309; }
  .pill.rejected { background: #fff1f2; border-color: #fecdd3; }
  .pill.rejected .pill-value { color: #be123c; }

  /* ── TABLE ── */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10.5px;
  }

  thead tr {
    background: #1a1a2e;
    color: #ffffff;
  }

  thead th {
    padding: 9px 11px;
    text-align: left;
    font-weight: 600;
    font-size: 9.5px;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    white-space: nowrap;
  }

  thead th:first-child { border-radius: 6px 0 0 0; }
  thead th:last-child  { border-radius: 0 6px 0 0; }

  tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.1s;
  }

  tbody tr:nth-child(even) { background: #f9fafb; }
  tbody tr:last-child { border-bottom: none; }

  td {
    padding: 8px 11px;
    color: #374151;
    vertical-align: middle;
  }

  td.no {
    font-weight: 600;
    color: #9ca3af;
    font-size: 9.5px;
    width: 28px;
  }

  td.name { font-weight: 600; color: #111827; }

  td.time {
    font-family: 'Courier New', monospace;
    font-size: 10px;
    color: #4f46e5;
    font-weight: 700;
  }

  td.reason {
    max-width: 160px;
    color: #6b7280;
    font-style: italic;
  }

  /* ── STATUS BADGE ── */
  .badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
  }

  .badge-approved  { background: #dcfce7; color: #15803d; }
  .badge-waiting   { background: #fef9c3; color: #854d0e; }
  .badge-hrd       { background: #dbeafe; color: #1d4ed8; }
  .badge-rejected  { background: #ffe4e6; color: #be123c; }
  .badge-unknown   { background: #f3f4f6; color: #6b7280; }
  .badge-cancelled { background: #f3f4f6; color: #6b7280; }

  /* ── FOOTER ── */
  .footer {
    margin-top: 28px;
    padding-top: 14px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
  }

  .footer-note {
    font-size: 9px;
    color: #9ca3af;
  }

  .signature-block {
    text-align: center;
    font-size: 9.5px;
    color: #374151;
  }

  .signature-line {
    width: 160px;
    border-top: 1px solid #374151;
    margin: 36px auto 4px;
  }

  .signature-title {
    font-size: 9px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
</style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">

  {{-- Kiri: label sistem --}}
  <div class="header-left">
    <div class="company-name">Internal System</div>
    <div class="company-sub">HRIS Module</div>
  </div>

  {{-- Tengah: logo --}}
  <div class="header-center">
    <img
      src="data:{{ $logoMime }};base64,{{ $logoBase64 }}"
      alt="Logo"
    />
    <div class="company-divider"></div>
    <div class="company-full-name">PT. Artabumi Sentra Industri</div>
  </div>

  {{-- Kanan: judul dokumen --}}
  <div class="header-right">
    <div class="doc-title">Late Working Permit</div>
    <div class="doc-meta">
      Generated: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp;
      Total Records: {{ $data->count() }}
    </div>
  </div>

</div>

<div class="accent-bar"></div>

{{-- ── TABLE ── --}}
<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Employee</th>
      <th>Department</th>
      <th>Date</th>
      <th>Time In</th>
      <th>Reason</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($data as $row)
    <tr>
      <td class="no">{{ $loop->iteration }}</td>
      <td class="name">{{ $row->user->name ?? '-' }}</td>
      <td>{{ $row->department->nama_department ?? '-' }}</td>
      <td>{{ $row->tanggal->format('d M Y') }}</td>
      <td class="time">{{ \Carbon\Carbon::parse($row->jam_masuk)->format('H:i') }}</td>
      <td class="reason">{{ $row->alasan ?? '-' }}</td>
    <td>
    <span class="badge badge-{{ $row->getStageColor() }}">
        {{ $row->getStageLabel() }}
    </span>
</td>
    </tr>
    @empty
    <tr>
      <td colspan="7" style="text-align: center; padding: 24px; color: #9ca3af; font-style: italic;">
        No data available
      </td>
    </tr>
    @endforelse
  </tbody>
</table>

{{-- ── FOOTER ── --}}
<div class="footer">
  <div class="footer-note">
    &copy; {{ date('Y') }} Internal System &mdash; PT. Artabumi Sentra Industri<br>
    This document is system-generated and valid with signature.
  </div>
  <div class="signature-block">
    <div class="signature-line"></div>
    <div>Irene Novita</div>
    <div class="signature-title">Human Resources Department</div>
  </div>
</div>

</body>
</html>
