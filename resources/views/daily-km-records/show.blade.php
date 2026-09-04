@extends('layouts.app')

@section('title', 'KM Qeydi - Məlumatlar')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📄 KM Qeydi - Məlumatlar</h1>
        <a href="{{ route('daily-km-records.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>

    {{-- Cari Qeydin Məlumatları --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">🚌 Avtobus</small>
                        <strong>{{ $record->bus->dqn ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Xətt №</small>
                        <strong>{{ $record->bus->route_number ?? '-' }}</strong> <!-- ✅ xett_no → route_number -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📅 Tarix</small>
                        <strong>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('d.m.Y') : '-' }}</strong> <!-- ✅ tarix → date -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📊 KM (Yürüş)</small>
                        <strong>{{ number_format($record->km, 0, ',', '.') }} km</strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📝 Qeyd</small>
                        <strong>{{ $record->notes ?? '-' }}</strong> <!-- ✅ qeyd → notes -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BÜTÜN KM TARİXÇƏSİ --}}
    <div class="section-title mt-4">
        📊 {{ $record->bus->dqn }} Avtobusunun KM Tarixçəsi
        <span class="badge bg-primary ms-2">{{ $history->count() }} qeyd</span>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>№</th>
                            <th>📅 Tarix</th>
                            <th>📊 KM</th>
                            <th>📝 Qeyd</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $index => $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->date ? \Carbon\Carbon::parse($item->date)->format('d.m.Y') : '-' }}</td> <!-- ✅ tarix → date -->
                            <td><strong>{{ number_format($item->km, 0, ',', '.') }} km</strong></td>
                            <td>{{ $item->notes ?? '-' }}</td> <!-- ✅ qeyd → notes -->
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('daily-km-records.show', $item) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('daily-km-records.edit', $item) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                Bu avtobus üçün KM qeydi yoxdur.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('daily-km-records.create') }}?bus_id={{ $record->bus_id }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Bu avtobusa KM əlavə et
        </a>
    </div>
</div>
@endsection
