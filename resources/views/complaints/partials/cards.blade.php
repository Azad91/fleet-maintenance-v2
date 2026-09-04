<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">📋 Kartlar</h5>
            <span class="badge bg-primary rounded-pill">Cəmi: {{ $complaints->total() }} ədəd</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>№</th>
                        <th>DQN</th>
                        <th>Xətt №</th>
                        <th>Yer</th>
                        <th>Şikayət</th>
                        <th>Status</th>
                        <th style="width: 140px;">Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody id="complaintsTableBody">
                    @forelse($complaints as $complaint)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $complaint->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $complaint->bus->route_number ?? '-' }}</td> <!-- ✅ xett_no → route_number -->
                        <td>
                            @if($complaint->yer == 'yol')
                                🛣️ Yol
                            @elseif($complaint->yer == 'qaraj')
                                🏠 Qaraj
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ Str::limit($complaint->shikayet ?? '-', 50) }}</td>
                        <td>
                            <span class="badge-status {{ str_replace(' ', '-', $complaint->status) }}">
                                {{ $complaint->status }}
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
                                    <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display:inline" onsubmit="return confirm('Əminsən?')">
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
                            Hələ kart yoxdur.
                            @can('create', App\Models\Complaint::class)
                                <a href="{{ route('complaints.create') }}">Yeni kart əlavə et!</a>
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
