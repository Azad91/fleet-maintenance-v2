@extends('reports.layouts.report-shell')

@section('report-content')
<div class="fleet-kpi-grid mb-4">
    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--blue">
            <i class="fas fa-arrow-right-arrow-left"></i>
        </span>
        <div>
            <span>{{ __('messages.transfers.report.total_transfers') }}</span>
            <strong>{{ $summary['total'] }}</strong>
            <small>{{ __('messages.transfers.report.in_period') }}</small>
        </div>
    </article>

    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--violet">
            <i class="fas fa-box"></i>
        </span>
        <div>
            <span>{{ __('messages.transfers.report.items_moved') }}</span>
            <strong>{{ number_format($summary['items_moved'], 0, ',', '.') }}</strong>
            <small>{{ __('messages.transfers.items_unit') }}</small>
        </div>
    </article>

    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--amber">
            <i class="fas fa-triangle-exclamation"></i>
        </span>
        <div>
            <span>{{ __('messages.transfers.report.disputed') }}</span>
            <strong>{{ $summary['disputed'] }}</strong>
            <small>{{ $summary['dispute_rate'] }}% {{ __('messages.transfers.report.dispute_rate') }}</small>
        </div>
    </article>

    <article class="fleet-kpi-card">
        <span class="fleet-kpi-card__icon fleet-kpi-card__icon--rose">
            <i class="fas fa-check-circle"></i>
        </span>
        <div>
            <span>{{ __('messages.transfers.report.received') }}</span>
            <strong>{{ $summary['received'] }}</strong>
            <small>{{ __('messages.transfers.report.successful') }}</small>
        </div>
    </article>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> {{ __('messages.transfers.report.by_status') }}
                </h5>
            </div>
            <div class="card-body">
                @forelse($summary['by_status'] as $status => $count)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>
                            <span class="badge bg-{{ \App\Enums\TransferStatus::from($status)->bootstrapColor() }}">
                                {{ \App\Enums\TransferStatus::from($status)->label() }}
                            </span>
                        </span>
                        <strong>{{ $count }}</strong>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        {{ __('messages.reports.content.no_data') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tags"></i> {{ __('messages.transfers.report.by_type') }}
                </h5>
            </div>
            <div class="card-body">
                @forelse($summary['by_type'] as $type => $count)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>
                            <span class="badge bg-info text-dark">
                                {{ \App\Enums\TransferType::from($type)->label() }}
                            </span>
                        </span>
                        <strong>{{ $count }}</strong>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        {{ __('messages.reports.content.no_data') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-arrow-up"></i> {{ __('messages.transfers.report.outbound') }}
                </h5>
            </div>
            <div class="card-body text-center">
                <div style="font-size: 42px; font-weight: 800; color: #2563eb;">
                    {{ $summary['outbound'] }}
                </div>
                <small class="text-muted">{{ __('messages.transfers.report.outbound_hint') }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-arrow-down"></i> {{ __('messages.transfers.report.inbound') }}
                </h5>
            </div>
            <div class="card-body text-center">
                <div style="font-size: 42px; font-weight: 800; color: #10b981;">
                    {{ $summary['inbound'] }}
                </div>
                <small class="text-muted">{{ __('messages.transfers.report.inbound_hint') }}</small>
            </div>
        </div>
    </div>
</div>
@endsection
