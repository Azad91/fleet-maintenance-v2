@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.reports.content.date') }}</th>
                        <th>{{ __('messages.warehouse.code') }}</th>
                        <th>{{ __('messages.warehouse.name') }}</th>
                        <th>{{ __('messages.warehouse.quantity') }}</th>
                        <th>{{ __('messages.warehouse.unit') }}</th>
                        <th>{{ __('messages.warehouse.price') }}</th>
                        <th>{{ __('messages.reports.content.user') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td>{{ $item->unit ?? '—' }}</td>
                            <td>{{ $item->price ? number_format($item->price, 2) . ' ₼' : '—' }}</td>
                            <td>{{ $item->creator?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_data') }}
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
