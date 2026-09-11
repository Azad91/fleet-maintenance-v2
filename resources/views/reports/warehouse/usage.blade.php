@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.complaints.part_code') }}</th>
                        <th>{{ __('messages.complaints.part_name') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.times_used') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total_used') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ $item->times_used }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--success">{{ number_format($item->total_used, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
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
