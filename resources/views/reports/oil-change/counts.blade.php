@extends('reports.layouts.report-shell')

@php
    use App\Enums\OilType;

    // The model casts `oil_type` to an enum, so we normalise the key
    // here to use the enum's VALUE (string) as the group key.
    $byPeriod = $rows->groupBy(fn ($r) => \Carbon\Carbon::parse($r->period_start)->format('Y-m'));

    $totalsByType = $rows
        ->groupBy(fn ($r) => $r->oil_type instanceof OilType ? $r->oil_type->value : $r->oil_type)
        ->map->sum('total');
@endphp

@section('report-content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.oil-change.counts') }}" class="row g-2 align-items-end">
            @foreach(request()->only(['period', 'from', 'to', 'brand_id']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('messages.reports.period.label') }}</label>
                <select name="bucket" class="form-select" onchange="this.form.submit()">
                    <option value="month" @selected($bucket === 'month')>{{ __('messages.reports.content.monthly') }}</option>
                    <option value="quarter" @selected($bucket === 'quarter')>{{ __('messages.reports.content.quarterly') }}</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="fleet-kpi-grid mb-4">
    @foreach(OilType::cases() as $type)
        <article class="fleet-kpi-card">
            <span class="fleet-kpi-card__icon fleet-kpi-card__icon--{{ $type->bootstrapColor() }}">
                <i class="fas fa-oil-can"></i>
            </span>
            <div>
                <span>{{ $type->label() }}</span>
                <strong>{{ (int) ($totalsByType[$type->value] ?? 0) }}</strong>
                <small>{{ __('messages.reports.content.in_period') }}</small>
            </div>
        </article>
    @endforeach
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.reports.period.label') }}</th>
                        <th>{{ __('messages.oil_change.type') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byPeriod as $periodKey => $group)
                        @foreach($group as $row)
                            <tr>
                                <td><strong>{{ $periodKey }}</strong></td>
                                <td>
                                    @php
                                        $typeEnum = $row->oil_type instanceof OilType
                                            ? $row->oil_type
                                            : OilType::from($row->oil_type);
                                    @endphp
                                    <span class="badge bg-{{ $typeEnum->bootstrapColor() }}">
                                        {{ $typeEnum->label() }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong style="font-size:15px;">{{ (int) $row->total }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity:.3;"></i>
                                {{ __('messages.reports.content.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
