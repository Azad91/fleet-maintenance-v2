<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">📋 {{ __('messages.complaints.title') }}</h5>
            <span class="badge bg-primary rounded-pill">
                {{ __('messages.common.total') }}: {{ $complaints->total() }} {{ __('messages.complaints.total_label') }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>№</th>
                        <th>{{ __('messages.buses.dqn') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.complaints.location') }}</th>
                        <th>{{ __('messages.complaints.complaint') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th style="width: 140px;">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $complaint->bus->dqn ?? '-' }}</strong></td>
                            <td>{{ $complaint->bus->route_number ?? '-' }}</td>
                            <td>
                                @if($complaint->yer === 'road')
                                    🛣️ {{ __('enums.location.road') }}
                                @elseif($complaint->yer === 'garage')
                                    🏠 {{ __('enums.location.garage') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ Str::limit($complaint->items->first()->description ?? '-', 50) }}</td>
                            <td>
                                <span class="badge-status {{ str_replace('_', '-', $complaint->status) }}">
                                    {{ __('enums.complaint_status.' . $complaint->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('view', $complaint)
                                        <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @can('update', $complaint)
                                        <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $complaint)
                                        <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display:inline" onsubmit="return confirm('{{ __('messages.common.confirm') }}')">
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
                                @can('create', App\Models\Complaint::class)
                                    <a href="{{ route('complaints.create') }}">{{ __('messages.complaints.new') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complaints->hasPages())
            <div class="pagination-wrapper">
                {{ $complaints->links() }}
            </div>
        @endif
    </div>
</div>