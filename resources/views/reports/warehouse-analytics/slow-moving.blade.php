@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.warehouse-analytics.slow-moving') }}" class="row g-2 align-items-end">
            @foreach(request()->only(['period', 'from', 'to', 'brand_id']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('messages.reports.content.days_since_use') }}</label>
                <select name="days" class="form-select" onchange="this.form.submit()">
                    @foreach([30, 60, 90, 180, 365] as $d)
                        <option value="{{ $d }}" @selected($days === $d)>{{ $d }} gün</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-warning mb-3">
    <i class="fas fa-triangle-exclamation"></i>
    {{ __('messages.reports.content.slow_moving_hint', ['days' => $days]) }}
</div>

@php $totalCapital = $rows->sum('tied_capital'); @endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose"><i class="fas fa-boxes-stacked"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.items') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.tied_capital') }}</span>
            <strong>{{ number_format($totalCapital, 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.total_value') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.warehouse.code') }}</th>
                        <th>{{ __('messages.warehouse.name') }}</th>
                        <th class="text-center">{{ __('messages.warehouse.quantity') }}</th>
                        <th>{{ __('messages.warehouse.unit') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.days_since_use') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.tied_capital') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $row->code }}</code></td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td class="text-center">{{ number_format($row->quantity, 0, ',', '.') }}</td>
                            <td>{{ $row->unit ?? '—' }}</td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $row->days_since_use ?? '—' }}</span>
                            </td>
                            <td class="text-end">
                                <strong style="color:#dc2626;">{{ number_format($row->tied_capital, 2) }} ₼</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-circle-check fa-2x mb-3 d-block" style="color:#10b981; opacity:.5;"></i>
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
