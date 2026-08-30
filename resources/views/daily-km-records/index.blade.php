@extends('layouts.app')

@section('title', 'Gündəlik KM Qeydləri')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>📊 Gündəlik KM Qeydləri</h1>
    @if(auth()->user()?->hasGarageRole(['admin', 'daily_km']))
    <div>
        <a href="{{ route('daily-km-records.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
        <a href="{{ route('daily-km-records.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Yeni KM Qeydi
        </a>
    </div>
    @endif
</div>

<!-- Axtarış -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="search" placeholder="DQN, Xətt № və ya Tarix ilə axtar..." value="{{ request('search') }}">
        <button class="btn btn-primary"><i class="bi bi-search"></i> Axtar</button>
        <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">Sıfırla</a>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>№</th>
                        <th>Avtobus (DQN)</th>
                        <th>Xətt №</th>
                        <th>Tarix</th>
                        <th>KM</th>
                        <th>Qeyd</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $record->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $record->bus->xett_no ?? '-' }}</td>
                        <td>{{ $record->tarix ? \Carbon\Carbon::parse($record->tarix)->format('d.m.Y') : '-' }}</td>
                        <td><strong>{{ number_format($record->km, 0, ',', '.') }} km</strong></td>
                        <td>{{ $record->qeyd ?? '-' }}</td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('daily-km-records.show', $record) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()?->hasGarageRole(['admin', 'daily_km']))
                                    <a href="{{ route('daily-km-records.edit', $record) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('daily-km-records.destroy', $record) }}" method="POST" style="display:inline" onsubmit="return confirm('Əminsən?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
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
                            <i class="bi bi-graph-up" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            Hələ KM qeydi yoxdur.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($records->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
