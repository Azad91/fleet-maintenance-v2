@extends('reports.layouts.report-shell')

@php
    use App\Enums\OilType;
@endphp

@section('report-content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.oil-change.history') }}" class="row g-2 align-items-end">
            @foreach(request()->only(['period', 'from', 'to', 'brand_id']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach

            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('messages.oil_change.type') }}</label>
                <select name="oil_type" class="form-select" onchange="this.form.submit()">
                    <option value="">{{ __('messages.oil_change.all_types') }}</option>
                    @foreach(OilType::cases() as $t)
                        <option value="{{ $t->value }}" @selected(($oilType?->value) === $t->value)>
                            {{ $t->icon() }} {{ $t->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.oil_change.type') }}</th>
                        <th>{{ __('messages.oil_change.brand') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.scheduled_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.actual_km') }}</th>
                        <th>{{ __('messages.oil_change.changed_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->bus?->dqn ?? '—' }}</strong></td>
                            <td>{{ $row->bus?->route_number ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $row->oil_type->bootstrapColor() }}">
                                    {{ $row->oil_type->icon() }} {{ $row->oil_type->label() }}
                                </span>
                            </td>
                            <td>
                                @if($row->oil_brand)
                                    <span class="badge bg-info text-dark">{{ $row->oil_brand }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ $row->scheduled_km ? number_format($row->scheduled_km, 0, '', '.') . ' km' : '—' }}
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($row->actual_km, 0, '', '.') }} km</strong>
                            </td>
                            <td>
                                {{ $row->changed_at?->format('d.m.Y') ?? $row->created_at?->format('d.m.Y') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-oil-can fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rows->isNotEmpty())
        <div class="card-footer text-muted small text-end">
            {{ __('messages.reports.content.records_count', ['count' => $rows->count()]) }}
        </div>
    @endif
</div>
@endsection
