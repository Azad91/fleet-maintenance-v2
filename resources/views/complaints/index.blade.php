@extends('layouts.app')

@section('title', __('messages.complaints.title'))

@section('content')
<div class="page-header">
    <h1>📋 {{ __('messages.complaints.title') }}</h1>
    <p class="text-muted">{{ __('messages.complaints.subtitle') }}</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2 flex-wrap">
        @can('create', App\Models\Complaint::class)
            <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> {{ __('messages.complaints.new') }}
            </a>
        @endcan
        @can('import', App\Models\Complaint::class)
            <a href="{{ route('complaints.import') }}" class="btn btn-success">
                <i class="bi bi-upload"></i> {{ __('messages.complaints.import') }}
            </a>
        @endcan
        <a href="{{ route('complaint-types.index') }}" class="btn btn-outline-info">
            <i class="bi bi-tags"></i> {{ __('messages.complaint_types.title') }}
        </a>
    </div>
    <span class="badge bg-primary rounded-pill">
        {{ __('messages.common.total') }}: {{ $complaints->total() }} {{ __('messages.complaints.total_label') }}
    </span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('messages.complaints.bus') }}</th>
                        <th>{{ __('messages.complaints.complaint') }}</th>
                        <th>{{ __('messages.complaints.complaint_type') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th>{{ __('messages.complaints.col_date') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                            <br>
                            <small class="text-muted">{{ $complaint->bus->route_number ?? '-' }}</small>
                        </td>
                        <td>
                            {{ Str::limit($complaint->items->first()->description ?? '-', 30) }}
                        </td>
                        <td>
                            @if($complaint->complaint_type)
                                <span class="badge bg-{{
                                    match($complaint->complaint_type) {
                                        'accident'    => 'danger',
                                        'breakdown'   => 'warning',
                                        'maintenance' => 'info',
                                        default       => 'secondary',
                                    }
                                }}">
                                    {{ __('enums.complaint_type.' . $complaint->complaint_type) }}
                                </span>
                            @else
                                <span class="badge bg-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{
                                match($complaint->status) {
                                    'completed'   => 'success',
                                    'in_progress' => 'warning',
                                    default       => 'secondary',
                                }
                            }}">
                                {{ __('enums.complaint_status.' . $complaint->status) }}
                            </span>
                        </td>
                        <td>{{ $complaint->created_at ? $complaint->created_at->format('d.m.Y') : '-' }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @can('view', $complaint)
                                    <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan

                                @can('update', $complaint)
                                    @if($complaint->status !== 'completed')
                                        <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                @endcan

                                @can('close', $complaint)
                                    @if($complaint->status !== 'completed')
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeModal{{ $complaint->id }}">
                                            <i class="bi bi-check-circle"></i> {{ __('messages.complaints.close_button') }}
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
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-clipboard" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            {{ __('messages.complaints.no_cards') }}
                            <br>
                            @can('create', App\Models\Complaint::class)
                                <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm mt-2">
                                    {{ __('messages.complaints.new') }}
                                </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $complaints->withQueryString()->links() }}
</div>

{{-- Close Modals --}}
@foreach($complaints as $complaint)
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
@endforeach
@endsection