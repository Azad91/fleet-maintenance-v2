<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('messages.complaints.bus') }}</th>
                        <th>{{ __('messages.buses.route_number') }}</th>
                        <th>{{ __('messages.complaints.location') }}</th>
                        <th>{{ __('messages.complaints.complaint') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        <tr>
                            <td>{{ $complaint->id }}</td>
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
                            <td>{{ Str::limit($complaint->items->first()->description ?? '-', 30) }}</td>
                            <td>
                                <span class="badge-status {{ str_replace('_', '-', $complaint->status) }}">
                                    {{ __('enums.complaint_status.' . $complaint->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('view', $complaint)
                                        <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @can('update', $complaint)
                                        <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $complaint)
                                        <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('messages.common.confirm') }}')">
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

        <span class="total-count d-none" data-count="{{ $complaints->count() }}"></span>
    </div>
</div>