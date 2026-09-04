@extends('layouts.app')

@section('title', 'Sürücü Məlumatları')

@section('content')
<div class="container">
    <h1>🧑‍✈️ Sürücü Məlumatları</h1>

    <div class="card">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Kod</small>
                        <strong>{{ $driver->code }}</strong> <!-- ✅ kodu → code -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Ad</small>
                        <strong>{{ $driver->first_name }}</strong> <!-- ✅ ad → first_name -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Soyad</small>
                        <strong>{{ $driver->last_name ?? '-' }}</strong> <!-- ✅ soyad → last_name -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Telefon</small>
                        <strong>{{ $driver->phone ?? '-' }}</strong> <!-- ✅ telefon → phone -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Vəzifəsi</small>
                        <strong>{{ $driver->position ?? '-' }}</strong> <!-- ✅ vezifesi → position -->
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Status</small>
                        <strong>
                            <span class="badge-status {{ $driver->is_active ? 'aktiv' : 'passiv' }}"> <!-- ✅ aktiv → is_active -->
                                {{ $driver->is_active ? '✅ Aktiv' : '❌ Passiv' }}
                            </span>
                        </strong>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">📝 Qeyd</small>
                        <strong>{{ $driver->notes ?? '-' }}</strong> <!-- ✅ qeyd → notes -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">⬅ Geri</a>
    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-warning">✏️ Redaktə Et</a>
</div>
@endsection
