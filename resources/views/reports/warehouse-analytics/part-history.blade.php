@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-info mb-3">
    <i class="fas fa-info-circle"></i>
    {{ __('messages.reports.content.part_history_hint') }}
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.warehouse-analytics.part-history') }}" class="row g-2 align-items-end">
            @foreach(request()->only(['period', 'from', 'to', 'brand_id']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('messages.reports.content.filter_code') }}</label>
                <input type="text" name="code" class="form-control" value="{{ $codeFilter }}" autocomplete="off">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('messages.reports.content.filter_dqn') }}</label>
                <input type="text" name="dqn" class="form-control" value="{{ $dqnFilter }}" autocomplete="off" style="text-transform: uppercase;">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> {{ __('messages.common.filter') }}
                </button>
            </div>
        </form>
    </div>
</div>

@php $totalCost = $rows->sum('line_cost'); @endphp

<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue"><i class="fas fa-list"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total') }}</span>
            <strong>{{ $rows->count() }}</strong>
            <small>{{ __('messages.reports.content.records') }}</small>
        </div>
    </article>
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber"><i class="fas fa-coins"></i></span>
        <div>
            <span>{{ __('messages.reports.content.total_cost') }}</span>
            <strong>{{ number_format($totalCost, 2) }} ₼</strong>
            <small>{{ __('messages.reports.content.in_period') }}</small>
        </div>
    </article>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.reports.content.date') }}</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.complaints.part_code') }}</th>
                        <th>{{ __('messages.complaints.part_name') }}</th>
                        <th class="text-end">{{ __('messages.complaints.used_qty') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.price_at_use') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.total_cost') }}</th>
                        <th>{{ __('messages.reports.content.source') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d.m.Y H:i') }}</td>
                            <td><strong>{{ $row->dqn }}</strong></td>
                            <td>{{ $row->route_number ?? '—' }}</td>
                            <td><code>{{ $row->code }}</code></td>
                            <td>{{ $row->part_name }}</td>
                            <td class="text-end"><strong>{{ (int) $row->used_quantity }}</strong></td>
                            <td class="text-end">
                                {{ $row->price_at_use !== null ? number_format((float) $row->price_at_use, 2) . ' ₼' : '—' }}
                            </td>
                            <td class="text-end">
                                @if($row->line_cost > 0)
                                    <strong>{{ number_format($row->line_cost, 2) }} ₼</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ __('messages.stock_sources.'.$row->source_type) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-list fa-2x mb-3 d-block" style="opacity:.3;"></i>
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
