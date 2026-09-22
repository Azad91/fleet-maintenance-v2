@php
    use App\Enums\ComplaintStatus;
    use App\Enums\ComplaintType;
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAllComplaints" title="{{ __('messages.common.select') }}">
                        </th>
                        <th>#</th>
                        <th>{{ __('messages.complaints.bus') }}</th>
                        <th>{{ __('messages.complaints.complaint') }}</th>
                        <th>{{ __('messages.complaints.location') }}</th>
                        <th>{{ __('messages.complaints.complaint_type') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th>📊 {{ __('messages.complaints.km') }}</th>
                        <th>{{ __('messages.complaints.col_date') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                    @php
                        // Prefer the operator-supplied work date over the
                        // DB insertion timestamp.
                        $displayDate = $complaint->reported_date
                            ?? $complaint->start_date
                            ?? $complaint->created_at;
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox"
                                   class="complaint-checkbox"
                                   value="{{ $complaint->id }}">
                        </td>
                        <td>{{ $complaints->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                            <br>
                            <small class="text-muted">{{ $complaint->bus->route_number ?? '-' }}</small>
                        </td>
                        <td>
                            {{ Str::limit($complaint->items->first()->description ?? '-', 40) }}
                        </td>
                        <td>
                            @if($complaint->yer?->isRoad())
                                🛣️ {{ $complaint->yer->label() }}
                            @elseif($complaint->yer?->isGarage())
                                🏠 {{ $complaint->yer->label() }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($complaint->complaint_type)
                                <span class="badge bg-{{ $complaint->complaint_type->bootstrapColor() }}">
                                    {{ $complaint->complaint_type->label() }}
                                </span>
                            @else
                                <span class="badge bg-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $complaint->status->bootstrapColor() }}">
                                {{ $complaint->status->label() }}
                            </span>
                        </td>
                        <td>
                            @if($complaint->km)
                                {{ number_format($complaint->km, 0, ',', '.') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            {{ $displayDate ? \Carbon\Carbon::parse($displayDate)->format('d.m.Y') : '—' }}
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @can('view', $complaint)
                                    <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan

                                @can('update', $complaint)
                                    @if(! $complaint->status->isCompleted())
                                        <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                @endcan

                                @can('close', $complaint)
                                    @if(! $complaint->status->isCompleted())
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeModal{{ $complaint->id }}">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    @endif
                                @endcan

                                @can('delete', $complaint)
                                    <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('messages.complaints.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-clipboard" style="font-size: 40px; display: block; margin-bottom: 10px; opacity: .3;"></i>
                            @if(request()->hasAny(['search', 'status', 'complaint_type', 'yer', 'date_from', 'date_to']))
                                {{ __('messages.common.no_data') }}
                                <br>
                                <a href="{{ route('complaints.index') }}" class="btn btn-sm btn-secondary mt-3">
                                    <i class="bi bi-x-circle"></i> {{ __('messages.common.reset') }}
                                </a>
                            @else
                                {{ __('messages.complaints.no_cards') }}
                                <br>
                                @can('create', App\Models\Complaint::class)
                                    <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm mt-2">
                                        {{ __('messages.complaints.new') }}
                                    </a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complaints->total() > 0)
            <div class="text-center text-muted small mt-2 mb-3">
                {{ __('messages.common.showing', [
                    'from'  => $complaints->firstItem(),
                    'to'    => $complaints->lastItem(),
                    'total' => $complaints->total(),
                ]) }}
            </div>
        @endif
    </div>

    @if($complaints->hasPages())
        <div class="pagination-wrapper d-flex justify-content-center py-3 border-top">
            {{-- Pagination links must point at the index route, not
                 the AJAX search route. The AJAX handler intercepts
                 clicks and routes them through performSearch()
                 anyway, but a non-JS fallback or a direct link visit
                 still needs the canonical URL. --}}
            {{ $complaints->withQueryString()->links(paginator: 'pagination::bootstrap-5') }}
        </div>
    @endif
</div>

{{-- Total count for the header badge (read by JS after each search) --}}
<span class="total-count d-none" data-count="{{ $complaints->total() }}"></span>

{{-- Close Modals — generated for the currently-visible complaints --}}
@foreach($complaints as $complaint)
    @if(! $complaint->status->isCompleted() && auth()->user()->can('close', $complaint))
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
@endforeach
