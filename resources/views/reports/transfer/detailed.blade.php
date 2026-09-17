@extends('reports.layouts.report-shell')

@section('report-content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.complaints.col_date') }}</th>
                        <th>{{ __('messages.transfers.from_garage') }}</th>
                        <th>{{ __('messages.transfers.to_garage') }}</th>
                        <th>{{ __('messages.transfers.type') }}</th>
                        <th>{{ __('messages.complaints.part_code') }}</th>
                        <th>{{ __('messages.complaints.part_name') }}</th>
                        <th class="text-end">{{ __('messages.transfers.declared_qty') }}</th>
                        <th class="text-end">{{ __('messages.transfers.received_qty') }}</th>
                        <th>{{ __('messages.transfers.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $toLabel = $row->to_garage_name
                                ? $row->to_garage_name . ($row->to_garage_code ? ' · ' . $row->to_garage_code : '')
                                : ($row->to_vehicle_name ? '🚐 ' . $row->to_vehicle_name : '—');

                            $diff = $row->received_quantity !== null
                                ? $row->received_quantity - $row->declared_quantity
                                : null;

                            try {
                                $status = \App\Enums\TransferStatus::from($row->status);
                                $statusLabel = $status->label();
                                $statusColor = $status->bootstrapColor();
                            } catch (\Throwable $e) {
                                $statusLabel = $row->status;
                                $statusColor = 'secondary';
                            }
                        @endphp
                        <tr>
                            <td>
                                <strong>#{{ $row->transfer_id }}</strong>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($row->created_at)->format('d.m.Y H:i') }}
                            </td>
                            <td>
                                <strong>{{ $row->from_garage_name }}</strong>
                                @if($row->from_garage_code)
                                    <br><code>{{ $row->from_garage_code }}</code>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $toLabel }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    {{ \App\Enums\TransferType::from($row->type)->label() }}
                                </span>
                            </td>
                            <td><code>{{ $row->code }}</code></td>
                            <td><strong>{{ $row->part_name }}</strong></td>
                            <td class="text-end"><strong>{{ $row->declared_quantity }}</strong></td>
                            <td class="text-end">
                                @if($row->received_quantity !== null)
                                    <strong>{{ $row->received_quantity }}</strong>
                                    @if($diff !== null && $diff !== 0)
                                        <br>
                                        <small class="{{ $diff > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-3 d-block" style="opacity: .3;"></i>
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
