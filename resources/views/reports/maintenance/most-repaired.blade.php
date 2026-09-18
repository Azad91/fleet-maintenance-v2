@extends('reports.layouts.report-shell')

@section('report-content')
@php $maxCards = $rows->max('cards_count') ?: 1; @endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">{{ __('messages.reports.content.rank') }}</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th style="width: 30%;">{{ __('messages.reports.content.cards_opened') }}</th>
                        <th class="text-center">{{ __('messages.reports.content.cards_closed') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.hours') }}</th>
                        <th class="text-end">{{ __('messages.reports.content.cost_label') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $percent = $maxCards > 0 ? round(($row->cards_count / $maxCards) * 100, 1) : 0; @endphp
                        <tr>
                            <td class="text-center">
                                @if($loop->iteration <= 3)
                                    <span class="fleet-status fleet-status--warning">🥇 #{{ $loop->iteration }}</span>
                                @else
                                    <strong>#{{ $loop->iteration }}</strong>
                                @endif
                            </td>
                            <td><strong>{{ $row->bus?->dqn ?? '—' }}</strong></td>
                            <td>{{ $row->bus?->route_number ?? '—' }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $percent }}%; height: 100%; background: linear-gradient(90deg, #dc2626, #f87171);"></div>
                                    </div>
                                    <strong style="white-space: nowrap;">{{ $row->cards_count }}</strong>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fleet-status fleet-status--success">{{ $row->completed_count }}</span>
                            </td>
                            <td class="text-end">{{ $row->total_hours }} {{ __('messages.reports.content.hours') }}</td>
                            <td class="text-end">
                                @if($row->total_cost > 0)
                                    <strong>{{ number_format($row->total_cost, 2) }} ₼</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-wrench fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.reports.content.no_activity') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rows->isNotEmpty())
        <div class="card-footer text-muted small text-end">
            {{ __('messages.reports.content.ranking_by_cards') }}
        </div>
    @endif
</div>
@endsection
