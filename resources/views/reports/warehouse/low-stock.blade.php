@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-warning mb-3">
    <i class="fas fa-triangle-exclamation"></i>
    {{ __('messages.reports.content.low_stock_hint') }}
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
                        <th class="text-center">{{ __('messages.reports.content.min_quantity') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.deficit') }}</th>
                        <th>{{ __('messages.warehouse.unit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $deficit = max(0, $item->minimum_quantity - $item->quantity); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td class="text-center">
                                <span class="fleet-status {{ $item->quantity <= 0 ? 'fleet-status--muted' : 'fleet-status--warning' }}">
                                    {{ number_format($item->quantity, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center">{{ number_format($item->minimum_quantity, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <strong style="color: #dc2626;">{{ number_format($deficit, 0, ',', '.') }}</strong>
                            </td>
                            <td>{{ $item->unit ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-circle-check fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_low_stock') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($items->isNotEmpty())
        <div class="card-footer text-muted small text-end">
            {{ __('messages.reports.content.records_count', ['count' => $items->count()]) }}
        </div>
    @endif
</div>
@endsection
