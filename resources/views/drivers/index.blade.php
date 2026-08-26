@extends('layouts.app')

@section('title', 'Sürücülər')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>🧑‍✈️ Sürücülər</h1>
    <div>
        <a href="{{ route('drivers.export') }}" class="btn btn-info">
            <i class="bi bi-download"></i> Excel - ə Endir
        </a>
        <a href="{{ route('drivers.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
        <a href="{{ route('drivers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Yeni Sürücü
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>Ad</th>
                        <th>Soyad</th>
                        <th>Telefon</th>
                        <th>Vəzifəsi</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $driver)
                    <tr>
                        <td><strong>{{ $driver->kodu }}</strong></td>
                        <td>{{ $driver->ad }}</td>
                        <td>{{ $driver->soyad ?? '-' }}</td>
                        <td>{{ $driver->telefon ?? '-' }}</td>
                        <td>{{ $driver->vezifesi ?? '-' }}</td>
                        <td>
                            <span class="badge-status {{ $driver->aktiv ? 'aktiv' : 'passiv' }}">
                                {{ $driver->aktiv ? '✅ Aktiv' : '❌ Passiv' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('drivers.destroy', $driver) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Əminsən?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-person" style="font-size:40px;display:block;margin-bottom:10px;"></i>
                            Hələ sürücü yoxdur.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $drivers->withQueryString()->links() }}
</div>
@endsection
