<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Avtobus (DQN)</th>
                        <th>Xətt</th>
                        <th>Yer</th>
                        <th>Şikayət</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                    <tr>
                        <td>{{ $complaint->id }}</td>
                        <td><strong>{{ $complaint->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $complaint->bus->xett_no ?? '-' }}</td>
                        <td>
                            @if($complaint->yer == 'yol')
                                🛣️ Yol
                            @elseif($complaint->yer == 'qaraj')
                                🏠 Qaraj
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ Str::limit($complaint->shikayet ?? '-', 30) }}</td>
                        <td>
                            <span class="badge-status {{ str_replace(' ', '-', $complaint->status) }}">
                                {{ $complaint->status }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(Auth::user()->hasGarageRole(['admin', 'complaint']))
                                    <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Əminsən?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-clipboard" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            @if(isset($dqn) || isset($xett_no) || isset($yer) || isset($shikayet))
                                Axtarış nəticəsində heç nə tapılmadı
                            @else
                                Hələ kart yoxdur. <a href="{{ route('complaints.create') }}">Yenisini əlavə et!</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ==================== PAGINATION ==================== -->
        @if($complaints->hasPages())
            <div class="pagination-wrapper">
                {{ $complaints->links() }}
            </div>
        @endif

        <!-- Toplam sayını gizli saxlamaq üçün -->
        <span class="total-count d-none" data-count="{{ $complaints->count() }}"></span>
    </div>
</div>
