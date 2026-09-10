<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>İş Kartı — Akt #{{ $complaint->id }}</title>
    <style>
        @page { margin: 22mm 16mm 18mm 16mm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }

        /* ============ HEADER ============ */
        .pdf-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .pdf-header__left, .pdf-header__right {
            display: table-cell;
            vertical-align: middle;
        }
        .pdf-header__right { text-align: right; }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .garage-name {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }
        .garage-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        .doc-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #0f172a;
            color: #fff;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.5px;
        }
        .doc-number {
            font-size: 10px;
            color: #475569;
            margin-top: 6px;
        }

        /* ============ TITLE ============ */
        .doc-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 2px;
            margin: 4px 0 14px;
            padding: 6px 0;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }

        /* ============ SECTIONS ============ */
        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            background: #f1f5f9;
            padding: 5px 8px;
            border-left: 3px solid #0f172a;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ============ INFO GRID ============ */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 11px;
        }
        .info-table td.label {
            width: 24%;
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        /* ============ COMPLAINTS LIST ============ */
        .complaint-list {
            margin: 0;
            padding: 0 0 0 6px;
            list-style: none;
        }
        .complaint-list li {
            padding: 4px 8px;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 11px;
        }
        .complaint-list li:last-child { border-bottom: none; }
        .complaint-list .num {
            display: inline-block;
            width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            border-radius: 50%;
            background: #dbeafe;
            color: #1e40af;
            font-weight: bold;
            font-size: 10px;
            margin-right: 6px;
        }

        /* ============ PARTS TABLE ============ */
        .parts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .parts-table th {
            background: #0f172a;
            color: #fff;
            padding: 6px 8px;
            font-size: 10px;
            text-align: left;
            border: 1px solid #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .parts-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10.5px;
            vertical-align: top;
        }
        .parts-table td.center { text-align: center; }
        .parts-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* ============ WORK DONE ============ */
        .work-block {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 3px;
            font-size: 11px;
            min-height: 40px;
        }

        /* ============ STATUS BADGE ============ */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge--pending  { background: #fef3c7; color: #92400e; }
        .badge--progress { background: #dbeafe; color: #1e40af; }
        .badge--done     { background: #d1fae5; color: #065f46; }
        .badge--default  { background: #e2e8f0; color: #334155; }

        /* ============ SIGNATURES ============ */
        .signature-row {
            display: table;
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            padding: 0 8px;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #0f172a;
            padding-top: 5px;
            margin-top: 34px;
            font-size: 10px;
            color: #334155;
            font-weight: bold;
        }
        .signature-role {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ============ FOOTER ============ */
        .pdf-footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        /* ============ HELPERS ============ */
        .text-muted  { color: #64748b; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .mt-0 { margin-top: 0; }
        .mb-0 { margin-bottom: 0; }
    </style>
</head>
<body>

    {{-- ==================== HEADER ==================== --}}
    <div class="pdf-header">
        <div class="pdf-header__left">
            <div class="company-name">{{ $company->name ?? 'ŞİRKƏT' }}</div>
            <div class="garage-name">
                <strong>{{ $garage->name ?? 'QARAJ' }}</strong>
                @if($garage->code ?? null) · {{ $garage->code }} @endif
            </div>
            @if(($garage->address ?? null) || ($garage->phone ?? null))
                <div class="garage-meta">
                    @if($garage->address ?? null){{ $garage->address }}@endif
                    @if(($garage->address ?? null) && ($garage->phone ?? null)) · @endif
                    @if($garage->phone ?? null)☎ {{ $garage->phone }}@endif
                </div>
            @endif
        </div>
        <div class="pdf-header__right">
            <div class="doc-badge">İŞ KARTI / AKT</div>
            <div class="doc-number">
                № <strong>{{ str_pad($complaint->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                {{ now()->format('d.m.Y') }}
            </div>
        </div>
    </div>

    <div class="doc-title">TEXNİKİ XİDMƏT AKTI</div>

    {{-- ==================== BUS INFO ==================== --}}
    <div class="section">
        <div class="section-title">Avtobus Məlumatları</div>
        <table class="info-table">
            <tr>
                <td class="label">DQN</td>
                <td><strong>{{ $complaint->bus->dqn ?? '—' }}</strong></td>
                <td class="label">Xətt №</td>
                <td>{{ $complaint->bus->route_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Layihə</td>
                <td>{{ $complaint->bus->bus_project ?? '—' }}</td>
                <td class="label">VIN</td>
                <td>{{ $complaint->bus->vin ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Yer</td>
                <td>
                    @if($complaint->yer === 'yol')
                        🛣️ Yol
                    @elseif($complaint->yer === 'qaraj')
                        🏠 Qaraj
                    @else
                        —
                    @endif
                </td>
                <td class="label">KM</td>
                <td>{{ $complaint->km ? number_format($complaint->km, 0, ',', '.') . ' km' : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Sürücü</td>
                <td>
                    @if($complaint->driver)
                        {{ $complaint->driver->full_name }}
                        @if($complaint->driver->code)({{ $complaint->driver->code }})@endif
                    @else
                        {{ $complaint->driver_name ?? '—' }}
                    @endif
                </td>
                <td class="label">Status</td>
                <td>
                    @php
                        $statusClass = match ($complaint->status) {
                            'gözləmədə' => 'badge--pending',
                            'işdə' => 'badge--progress',
                            'həll olundu' => 'badge--done',
                            default => 'badge--default',
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $complaint->status }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ==================== COMPLAINTS ==================== --}}
    <div class="section">
        <div class="section-title">Şikayətlər</div>
        @if($complaint->items->count() > 0)
            <ul class="complaint-list">
                @foreach($complaint->items as $index => $item)
                    <li>
                        <span class="num">{{ $index + 1 }}</span>
                        {{ trim($item->description) }}
                        @if($item->type)
                            <span class="text-muted">({{ $item->type }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <div class="work-block text-muted">Şikayət qeyd edilməyib.</div>
        @endif
    </div>

    {{-- ==================== TIME ==================== --}}
    <div class="section">
        <div class="section-title">Tarix və Vaxt</div>
        <table class="info-table">
            <tr>
                @if($complaint->yer === 'yol' && $complaint->reported_date)
                    <td class="label">Bildirilmə</td>
                    <td>{{ \Carbon\Carbon::parse($complaint->reported_date)->format('d.m.Y') }}
                        {{ $complaint->reported_time ? '· ' . $complaint->reported_time : '' }}</td>
                @endif
                <td class="label">İşə başlama</td>
                <td>{{ $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('d.m.Y') : '—' }}
                    {{ $complaint->start_time ? '· ' . $complaint->start_time : '' }}</td>
            </tr>
            <tr>
                <td class="label">İşin bitməsi</td>
                <td>{{ $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('d.m.Y') : '—' }}
                    {{ $complaint->end_time ? '· ' . $complaint->end_time : '' }}</td>
                <td class="label">Növ</td>
                <td>
                    @php
                        $typeLabel = match ($complaint->complaint_type) {
                            'qezali' => '🚗 Qəzalı',
                            'nasazliq' => '⚠️ Nasazlıq',
                            'texniki_xidmet' => '🔧 Texniki Xidmət',
                            default => '—',
                        };
                    @endphp
                    {{ $typeLabel }}
                </td>
            </tr>
        </table>
    </div>

    {{-- ==================== PARTS ==================== --}}
    @if($complaint->details->count() > 0)
        <div class="section">
            <div class="section-title">İstifadə Olunan Detallar</div>
            <table class="parts-table">
                <thead>
                    <tr>
                        <th style="width: 6%;" class="center">#</th>
                        <th style="width: 18%;">Kod</th>
                        <th>Ad</th>
                        <th style="width: 10%;" class="center">Miqdar</th>
                        <th style="width: 26%;">İşi Görən İşçi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($complaint->details as $idx => $detail)
                        @php
                            $employee = $detail->employee_id
                                ? $employeesById->get($detail->employee_id)
                                : null;
                            $employeeName = $employee?->full_name_with_position ?? '—';
                        @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td><strong>{{ $detail->code ?? '—' }}</strong></td>
                            <td>
                                {{ $detail->name ?? '—' }}
                                @if($detail->notes)
                                    <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">
                                        <em>{{ $detail->notes }}</em>
                                    </div>
                                @endif
                            </td>
                            <td class="center">{{ $detail->used_quantity ?? 0 }}</td>
                            <td>{{ $employeeName }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ==================== WORK DONE ==================== --}}
    <div class="section">
        <div class="section-title">Görülən İşlər</div>
        <div class="work-block">
            {{ $complaint->work_done_by ?: '—' }}
        </div>
    </div>

    {{-- ==================== SIGNATURES ==================== --}}
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line">İşi Görən</div>
            <div class="signature-role">Usta / Mexanik</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">Yoxlayan</div>
            <div class="signature-role">Baş Usta</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">Təsdiq Edən</div>
            <div class="signature-role">Qaraj Rəhbəri</div>
        </div>
    </div>

    {{-- ==================== FOOTER ==================== --}}
    <div class="pdf-footer">
        Bu sənəd {{ now()->format('d.m.Y H:i') }} tarixində avtomatik yaradıldı ·
        Fleet Control · ID #{{ $complaint->id }}
    </div>

</body>
</html>
