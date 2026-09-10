@php
    /**
     * CSS faylına tam yol (Windows və Linux üçün uyğun).
     * DomPDF `file://` protokolu ilə lokal faylları oxuya bilir.
     */
    $cssPath = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', public_path('css/pdf-akt.css'));

    $statusClass = match ($complaint->status) {
        'gözləmədə' => 'badge--pending',
        'işdə' => 'badge--progress',
        'həll olundu' => 'badge--done',
        default => 'badge--default',
    };

    $typeLabel = match ($complaint->complaint_type) {
        'qezali' => '🚗 Qəzalı',
        'nasazliq' => '⚠️ Nasazlıq',
        'texniki_xidmet' => '🔧 Texniki Xidmət',
        default => '—',
    };

    $yerLabel = match ($complaint->yer) {
        'yol' => '🛣️ Yol',
        'qaraj' => '🏠 Qaraj',
        default => '—',
    };
@endphp
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>İş Kartı — Akt #{{ $complaint->id }}</title>
    <link rel="stylesheet" href="{{ $cssPath }}">
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
                <td>{{ $yerLabel }}</td>
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
                    <td>
                        {{ \Carbon\Carbon::parse($complaint->reported_date)->format('d.m.Y') }}
                        {{ $complaint->reported_time ? '· ' . $complaint->reported_time : '' }}
                    </td>
                @endif
                <td class="label">İşə başlama</td>
                <td>
                    {{ $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('d.m.Y') : '—' }}
                    {{ $complaint->start_time ? '· ' . $complaint->start_time : '' }}
                </td>
            </tr>
            <tr>
                <td class="label">İşin bitməsi</td>
                <td>
                    {{ $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('d.m.Y') : '—' }}
                    {{ $complaint->end_time ? '· ' . $complaint->end_time : '' }}
                </td>
                <td class="label">Növ</td>
                <td>{{ $typeLabel }}</td>
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
                        <th class="text-center" style="width: 6%;">#</th>
                        <th style="width: 18%;">Kod</th>
                        <th>Ad</th>
                        <th class="text-center" style="width: 10%;">Miqdar</th>
                        <th style="width: 26%;">İşi Görən İşçi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($complaint->details as $idx => $detail)
                        @php
                            $employee = $detail->employee_id
                                ? $employeesById->get($detail->employee_id)
                                : null;
                        @endphp
                        <tr>
                            <td class="center">{{ $idx + 1 }}</td>
                            <td><strong>{{ $detail->code ?? '—' }}</strong></td>
                            <td>
                                {{ $detail->name ?? '—' }}
                                @if($detail->notes)
                                    <div class="part-note"><em>{{ $detail->notes }}</em></div>
                                @endif
                            </td>
                            <td class="center">{{ $detail->used_quantity ?? 0 }}</td>
                            <td>{{ $employee?->full_name_with_position ?? '—' }}</td>
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
