@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.reports.content.user') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.created') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.updated') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.deleted') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $row->user?->name ?? __('messages.reports.content.unknown_user') }}</strong>
                                <br>
                                <small class="text-muted">{{ $row->user?->email ?? '—' }}</small>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--success">{{ $row->created_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--warning">{{ $row->updated_count }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--muted">{{ $row->deleted_count }}</span>
                            </td>
                            <td class="text-center">
                                <strong>{{ $row->total_actions }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
