@extends('layouts.app')

@section('title', 'Complaint Details')

@section('content')
<div class="card complaint-show-card">
    <div class="card-header complaint-show-card__header d-flex justify-content-between align-items-center">
        <h4 class="mb-0 complaint-show-card__title">📋 Complaint Details</h4>
        <div class="d-flex align-items-center gap-2">
            @can('view', $complaint)
                <a href="{{ route('complaints.pdf', $complaint) }}" target="_blank" rel="noopener" class="btn btn-sm complaint-show-card__pdf-btn">
                    <i class="bi bi-file-earmark-pdf"></i> PDF / Print
                </a>
            @endcan
            <span class="badge-status {{ str_replace(' ', '-', $complaint->status) }}">{{ $complaint->status }}</span>
        </div>
    </div>
    <div class="card-body complaint-show-card__body">
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-bus-front me-2"></i>Bus Information</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>DQN</small>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>Route No</small>
                            <strong>{{ $complaint->bus->route_number ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>Location</small>
                            <strong>
                                @if($complaint->yer == 'yol')
                                    🛣️ Road
                                @elseif($complaint->yer == 'qaraj')
                                    🏠 Garage
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>🧑‍✈️ Driver</small>
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

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-clipboard me-2"></i>Complaints</h6>
                @php
                    $shikayetler = $complaint->items->pluck('description')->toArray();
                @endphp

                @if(count($shikayetler) > 0)
                    @foreach($shikayetler as $index => $shikayet)
                        <div class="complaint-show-card__entry">
                            <span class="complaint-show-card__entry-number">{{ $index + 1 }}</span>
                            <strong>{{ trim($shikayet) }}</strong>
                        </div>
                    @endforeach
                @else
                    <div class="complaint-show-card__empty">
                        <p>No complaint entered</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-clock me-2"></i>Date & Time</h6>
                <div class="row g-3">
                    @if($complaint->yer == 'yol')
                        <div class="col-md-4">
                            <div class="complaint-show-card__item">
                                <small>📅 Reported</small>
                                <strong>
                                    {{ $complaint->reported_date ? \Carbon\Carbon::parse($complaint->reported_date)->format('d.m.Y') : '-' }}
                                    {{ $complaint->reported_time ? ' - ' . $complaint->reported_time : '' }}
                                </strong>
                            </div>
                        </div>
                    @endif

                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 Start</small>
                            <strong>
                                {{ $complaint->start_date ? \Carbon\Carbon::parse($complaint->start_date)->format('d.m.Y') : '-' }}
                                {{ $complaint->start_time ? ' - ' . $complaint->start_time : '' }}
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 End</small>
                            <strong>
                                {{ $complaint->end_date ? \Carbon\Carbon::parse($complaint->end_date)->format('d.m.Y') : '-' }}
                                {{ $complaint->end_time ? ' - ' . $complaint->end_time : '' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="complaint-show-card__item complaint-show-card__item--wide">
                    <small>📊 KM (Mileage)</small>
                    <strong>{{ $complaint->km ? number_format($complaint->km, 0, ',', '.') . ' km' : '-' }}</strong>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-tools me-2"></i>🔧 Used Parts</h6>

                @php
                    $detallar = $complaint->details ?? collect();
                    $shikayetler = $complaint->items->pluck('description')->toArray();
                @endphp

                @if($detallar->count() > 0)
                    @foreach($detallar as $detal)
                        @php
                            $shikayetIndex = $detal->shikayet_index ?? 0;
                            $shikayetText = isset($shikayetler[$shikayetIndex]) ? trim($shikayetler[$shikayetIndex]) : "Complaint " . ($shikayetIndex + 1);
                        @endphp
                        <div class="complaint-show-card__detail">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small>📌 Related Complaint</small>
                                    <span class="complaint-show-card__pill">{{ $shikayetText }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small>Part Code</small>
                                    <strong>{{ $detal->code ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small>Part Name</small>
                                    <strong>{{ $detal->name ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small>Stock Qty</small>
                                    <strong>{{ $detal->stock_quantity ?? '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small>Used Qty</small>
                                    <strong class="complaint-show-card__danger">{{ $detal->used_quantity ?? '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small>👤 Employee</small>
                                    <strong>{{ $employeesById[$detal->employee_id ?? null]->full_name_with_position ?? '-' }}</strong>
                                </div>
                            </div>
                            @if(!empty($detal->notes))
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="complaint-show-card__note">
                                            <small>📝 Work Done</small>
                                            <strong>{{ $detal->notes }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="complaint-show-card__empty">
                        <p>No parts used</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-info-circle me-2"></i>ℹ️ Additional Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="complaint-show-card__item">
                            <small>Created</small>
                            <strong>{{ $complaint->created_at ? \Carbon\Carbon::parse($complaint->created_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="complaint-show-card__item">
                            <small>Last Updated</small>
                            <strong>{{ $complaint->updated_at ? \Carbon\Carbon::parse($complaint->updated_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary complaint-show-card__back-btn">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            @can('update', $complaint)
                @if($complaint->status != 'həll olundu')
                    <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
            @endcan
            @can('close', $complaint)
                @if($complaint->status != 'həll olundu')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#closeModal{{ $complaint->id }}">
                        <i class="bi bi-check-circle"></i> Close
                    </button>
                @endif
            @endcan
        </div>
    </div>
</div>

<!-- Close Modal -->
@if($complaint->status != 'həll olundu' && auth()->user()->can('close', $complaint))
    <div class="modal fade" id="closeModal{{ $complaint->id }}" tabindex="-1" aria-labelledby="closeModalLabel{{ $complaint->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('complaints.close', $complaint) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="closeModalLabel{{ $complaint->id }}">
                            <i class="bi bi-lock"></i> Close Complaint
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>🚌 Bus:</strong> {{ $complaint->bus->dqn ?? '-' }}
                            ({{ $complaint->bus->route_number ?? '-' }})
                        </div>

                        <div class="mb-3">
                            <label for="end_date" class="form-label fw-bold">📅 End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label for="end_time" class="form-label fw-bold">🕐 End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required value="{{ date('H:i') }}">
                        </div>

                        <div class="mb-3">
                            <label for="work_done" class="form-label fw-bold">📝 Work Done <span class="text-danger">*</span></label>
                            <textarea name="work_done" class="form-control" rows="3" placeholder="Describe work done in detail..." required></textarea>
                            <small class="text-muted">At least 5 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('closeModal{{ $complaint->id }}');
        if (modal) {
            new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: false
            });
        }
    });
</script>
@endsection