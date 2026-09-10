@php
    $cssPath = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', public_path('css/pdf-akt.css'));

    $statusClass = match ($complaint->status) {
        'pending'     => 'badge--pending',
        'in_progress' => 'badge--progress',
        'completed'   => 'badge--done',
        default       => 'badge--default',
    };

    $typeLabel = $complaint->complaint_type
        ? __('enums.complaint_type.' . $complaint->complaint_type)
        : '—';

    $yerLabel = $complaint->yer
        ? __('enums.location.' . $complaint->yer)
        : '—';

    $statusLabel = __('enums.complaint_status.' . $complaint->status);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.complaints.pdf_title') }} — #{{ $complaint->id }}</title>
    <link rel="stylesheet" href="{{ $cssPath }}">
</head>
<body>

    {{-- HEADER --}}
    <div class="pdf-header">
        <div class="pdf-header__left">
            <div class="company-name">{{ $company->name ?? __('messages.common.company') }}</div>
            <div class="garage-name">
                <strong>{{ $garage->name ?? __('messages.common.garage') }}</strong>
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
            <div class="doc-badge">{{ __('messages.complaints.pdf_badge') }}</div>
            <div class="doc-number">
                № <strong>{{ str_pad($complaint->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                {{ now()->format('d.m.Y') }}
            </div>
        </div>
    </div>

    <div class="doc-title">{{ __('messages.complaints.pdf_title') }}</div>

    {{-- BUS INFO --}}
    <div class="section">
        <div class="section-title">{{ __('messages.complaints.pdf_bus_info') }}</div>
        <table class="info-table">
            <tr>
                <td class="label">{{ __('messages.buses.dqn') }}</td>
                <td><strong>{{ $complaint->bus->dqn ?? '—' }}</strong></td>
                <td class="label">{{ __('messages.buses.route_number') }}</td>
                <td>{{ $complaint->bus->route_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.buses.bus_project') }}</td>
                <td>{{ $complaint->bus->bus_project ?? '—' }}</td>
                <td class="label">{{ __('messages.buses.vin') }}</td>
                <td>{{ $complaint->bus->vin ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.complaints.location') }}</td>
                <td>{{ $yerLabel }}</td>
                <td class="label">{{ __('messages.complaints.km') }}</td>
                <td>{{ $complaint->km ? number_format($complaint->km, 0, ',', '.') . ' km' : '—' }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.complaints.driver') }}</td>
                <td>
                    @if($complaint->driver)
                        {{ $complaint->driver->full_name }}
                        @if($complaint->driver->code)({{ $complaint->driver->code }})@endif
                    @else
                        {{ $complaint->driver_name ?? '—' }}
                    @endif
                </td>
                <td class="label">{{ __('messages.common.status') }}</td>
                <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
            </tr>
        </table>
    </div>

    {{-- COMPLAINTS --}}
    <div class="section">
        <div class="section-title">{{ __('messages.complaints.pdf_complaints') }}</div>
        @if($complaint->items->count() > 0)
            <ul class="complaint-list">
                @foreach($complaint->items as $index => $item)
                    <li>
                        <span class="num">{{ $index + 1 }}</span>
                        {{ trim($item->description) }}
                    </li>
                @endforeach
            </ul>
        @else
            <div class="work-block text-muted">{{ __('messages.complaints.no_complaint_entered') }}</div>
        @endif
    </div>

    {{-- TIME --}}
    <div class="section">
        <div class="section-title">{{ __('messages.complaints.pdf_time') }}</div>
        <table class="info-table">
            <tr>
                @if($complaint->yer === 'road' && $complaint->reported_date)
                    <td class="label">{{ __('messages.complaints.reported_date') }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($complaint->reported_date)->format('d.m.Y') }}
                        {{ $complaint->reported_time ? '· ' . $complaint->reported_time : '' }}
                    </td>
                @endif
                <td class="label">{{ __('messages.complaints.start_date') }}</td>
                <td>
                    {{ $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('d.m.Y') : '—' }}
                    {{ $complaint->start_time ? '· ' . $complaint->start_time : '' }}
                </td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.complaints.end_date') }}</td>
                <td>
                    {{ $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('d.m.Y') : '—' }}
                    {{ $complaint->end_time ? '· ' . $complaint->end_time : '' }}
                </td>
                <td class="label">{{ __('messages.complaints.complaint_type') }}</td>
                <td>{{ $typeLabel }}</td>
            </tr>
        </table>
    </div>

    {{-- PARTS --}}
    @if($complaint->details->count() > 0)
        <div class="section">
            <div class="section-title">{{ __('messages.complaints.pdf_parts') }}</div>
            <table class="parts-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 6%;">#</th>
                        <th style="width: 18%;">{{ __('messages.complaints.part_code') }}</th>
                        <th>{{ __('messages.complaints.part_name') }}</th>
                        <th class="text-center" style="width: 10%;">{{ __('messages.complaints.used_qty') }}</th>
                        <th style="width: 26%;">{{ __('messages.complaints.employee') }}</th>
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

    {{-- WORK DONE --}}
    <div class="section">
        <div class="section-title">{{ __('messages.complaints.work_done') }}</div>
        <div class="work-block">{{ $complaint->work_done_by ?: '—' }}</div>
    </div>

    {{-- SIGNATURES --}}
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line">{{ __('messages.complaints.pdf_sig_done') }}</div>
            <div class="signature-role">{{ __('messages.complaints.pdf_sig_done_role') }}</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">{{ __('messages.complaints.pdf_sig_checked') }}</div>
            <div class="signature-role">{{ __('messages.complaints.pdf_sig_checked_role') }}</div>
        </div>
        <div class="signature-cell">
            <div class="signature-line">{{ __('messages.complaints.pdf_sig_approved') }}</div>
            <div class="signature-role">{{ __('messages.complaints.pdf_sig_approved_role') }}</div>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="pdf-footer">
        {{ __('messages.complaints.pdf_footer', ['date' => now()->format('d.m.Y H:i'), 'id' => $complaint->id]) }}
    </div>

</body>
</html>