@extends('reports.layouts.report-shell')

@section('report-content')
<div class="alert alert-warning mb-3">
    <i class="fas fa-triangle-exclamation"></i>
    {{ __('messages.reports.content.recurring_hint') }}
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.complaints.complaint') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total') }}</th>
                        <th>{{ __('messages.reports.content.last_occurrence') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $row->description }}</strong></td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ (int) $row->total }}</span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($row->last_occurrence)->format('d.m.Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="fas fa-check-circle fa-2x mb-3 d-block" style="color:#10b981; opacity:.5;"></i>
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
