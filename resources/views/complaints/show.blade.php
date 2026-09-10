@extends('layouts.app')

@section('title', __('messages.complaints.details_title'))

@section('content')
<div class="card complaint-show-card">
    <div class="card-header complaint-show-card__header d-flex justify-content-between align-items-center">
        <h4 class="mb-0 complaint-show-card__title">📋 {{ __('messages.complaints.details_title') }}</h4>
        <div class="d-flex align-items-center gap-2">
            @can('view', $complaint)
                <a href="{{ route('complaints.pdf', $complaint) }}" target="_blank" rel="noopener" class="btn btn-sm complaint-show-card__pdf-btn">
                    <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.complaints.pdf_print') }}
                </a>
            @endcan
            <span class="badge-status {{ str_replace('_', '-', $complaint->status) }}">
                {{ __('enums.complaint_status.' . $complaint->status) }}
            </span>
        </div>
    </div>
    <div class="card-body complaint-show-card__body">
        {{-- Bus info --}}
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title">
                    <i class="bi bi-bus-front me-2"></i>{{ __('messages.complaints.bus_info') }}
                </h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>{{ __('messages.buses.dqn') }}</small>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>{{ __('messages.buses.route_number') }}</small>
                            <strong>{{ $complaint->bus->route_number ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>{{ __('messages.complaints.location') }}</small>
                            <strong>
                                @if($complaint->yer === 'road')
                                    🛣️ {{ __('enums.location.road') }}
                                @elseif($complaint->yer === 'garage')
                                    🏠 {{ __('enums.location.garage') }}
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>🧑‍✈️ {{ __('messages.complaints.driver') }}</small>
                            <strong>
                                @if($complaint->driver)
                                    {{ $complaint->driver->full_name }} ({{ $complaint->driver->code }})
                                @else
                                    {{ $complaint->driver_name ?? '-' }}
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Complaints list --}}
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title">
                    <i class="bi bi-clipboard me-2"></i>{{ __('messages.complaints.complaints_list') }}
                </h6>
                @php $complaintsList = $complaint->items->pluck('description')->toArray(); @endphp

                @if(count($complaintsList) > 0)
                    @foreach($complaintsList as $index => $description)
                        <div class="complaint-show-card__entry">
                            <span class="complaint-show-card__entry-number">{{ $index + 1 }}</span>
                            <strong>{{ trim($description) }}</strong>
                        </div>
                    @endforeach
                @else
                    <div class="complaint-show-card__empty">
                        <p>{{ __('messages.complaints.no_complaint_entered') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Date & Time --}}
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title">
                    <i class="bi bi-clock me-2"></i>{{ __('messages.complaints.date_time') }}
                </h6>
                <div class="row g-3">
                    @if($complaint->yer === 'road')
                        <div class="col-md-4">
                            <div class="complaint-show-card__item">
                                <small>📅 {{ __('messages.complaints.reported_date') }}</small>
                                <strong>
                                    {{ $complaint->reported_date ? \Carbon\Carbon::parse($complaint->reported_date)->format('d.m.Y') : '-' }}
                                    {{ $complaint->reported_time ? ' - ' . $complaint->reported_time : '' }}
                                </strong>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 {{ __('messages.complaints.start_date') }}</small>
                            <strong>
                                {{ $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('d.m.Y') : '-' }}
                                {{ $complaint->start_time ? ' - ' . $complaint->start_time : '' }}
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 {{ __('messages.complaints.end_date') }}</small>
                            <strong>
                                {{ $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('d.m.Y') : '-' }}
                                {{ $complaint->end_time ? ' - ' . $complaint->end_time : '' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KM --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="complaint-show-card__item complaint-show-card__item--wide">
                    <small>📊 {{ __('messages.complaints.km') }}</small>
                    <strong>{{ $complaint->km ? number_format($complaint->km, 0, ',', '.') . ' km' : '-' }}</strong>
                </div>
            </div>
        </div>

        {{-- Parts --}}
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title">
                    <i class="bi bi-tools me-2"></i>🔧 {{ __('messages.complaints.used_parts') }}
                </h6>
                @php
                    $details = $complaint->details ?? collect();
                    $complaintsList = $complaint->items->pluck('description')->toArray();
                @endphp

                @if($details->count() > 0)
                    @foreach($details as $detail)
                        @php
                            $shikayetIndex = $detail->shikayet_index ?? 0;
                            $shikayetText = isset($complaintsList[$shikayetIndex])
                                ? trim($complaintsList[$shikayetIndex])
                                : "Complaint " . ($shikayetIndex + 1);
                        @endphp
                        <div class="complaint-show-card__detail">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small>📌 {{ __('messages.complaints.related_complaint') }}</small>
                                    <span class="complaint-show-card__pill">{{ $shikayetText }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small>{{ __('messages.complaints.part_code') }}</small>
                                    <strong>{{ $detail->code ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small>{{ __('messages.complaints.part_name') }}</small>
                                    <strong>{{ $detail->name ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small>{{ __('messages.complaints.stock_qty') }}</small>
                                    <strong>{{ $detail->stock_quantity ?? '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small>{{ __('messages.complaints.used_qty') }}</small>
                                    <strong class="complaint-show-card__danger">{{ $detail->used_quantity ?? '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small>👤 {{ __('messages.complaints.employee') }}</small>
                                    <strong>{{ $employeesById[$detail->employee_id ?? null]->full_name_with_position ?? '-' }}</strong>
                                </div>
                            </div>
                            @if(!empty($detail->notes))
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="complaint-show-card__note">
                                            <small>📝 {{ __('messages.complaints.work_done') }}</small>
                                            <strong>{{ $detail->notes }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="complaint-show-card__empty">
                        <p>{{ __('messages.complaints.no_parts_used') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Additional info --}}
        <div class="row mb-3">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title">
                    <i class="bi bi-info-circle me-2"></i>ℹ️ {{ __('messages.complaints.info') }}
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="complaint-show-card__item">
                            <small>{{ __('messages.complaints.created') }}</small>
                            <strong>{{ $complaint->created_at ? \Carbon\Carbon::parse($complaint->created_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="complaint-show-card__item">
                            <small>{{ __('messages.complaints.last_updated') }}</small>
                            <strong>{{ $complaint->updated_at ? \Carbon\Carbon::parse($complaint->updated_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary complaint-show-card__back-btn">
                <i class="bi bi-arrow-left me-1"></i> {{ __('messages.common.back') }}
            </a>
            @can('update', $complaint)
                @if($complaint->status !== 'completed')
                    <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> {{ __('messages.common.edit') }}
                    </a>
                @endif
            @endcan
            @can('close', $complaint)
                @if($complaint->status !== 'completed')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#closeModal{{ $complaint->id }}">
                        <i class="bi bi-check-circle"></i> {{ __('messages.complaints.close_button') }}
                    </button>
                @endif
            @endcan
        </div>
    </div>
</div>

@if($complaint->status !== 'completed' && auth()->user()->can('close', $complaint))
    <div class="modal fade" id="closeModal{{ $complaint->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('complaints.close', $complaint) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-lock"></i> {{ __('messages.complaints.close_modal_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>🚌 {{ __('messages.complaints.bus') }}:</strong> {{ $complaint->bus->dqn ?? '-' }}
                            ({{ $complaint->bus->route_number ?? '-' }})
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('messages.complaints.end_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('messages.complaints.end_time') }} <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required value="{{ date('H:i') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('messages.complaints.work_done') }} <span class="text-danger">*</span></label>
                            <textarea name="work_done" class="form-control" rows="3" placeholder="{{ __('messages.complaints.work_done_placeholder') }}" required></textarea>
                            <small class="text-muted">{{ __('messages.complaints.work_done_hint') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> {{ __('messages.complaints.close_button') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection