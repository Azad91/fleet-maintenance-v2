@extends('layouts.app')

@section('title', 'İşçi Məlumatları')

@section('content')
<div class="container">
    <h1>👤 İşçi Məlumatları</h1>

    <div class="section-title">📋 Əsas Məlumatlar</div>

    <div class="field">
        <span class="label">ID:</span>
        <span class="value">{{ $employee->id }}</span>
    </div>
    <div class="field">
        <span class="label">Ad:</span>
        <span class="value">{{ $employee->first_name }}</span> <!-- ✅ ad → first_name -->
    </div>
    <div class="field">
        <span class="label">Soyad:</span>
        <span class="value">{{ $employee->last_name }}</span> <!-- ✅ soyad → last_name -->
    </div>
    <div class="field">
        <span class="label">Vəzifə:</span>
        <span class="value">{{ $employee->position }}</span> <!-- ✅ vezifesi → position -->
    </div>

    <div class="section-title">📊 Status</div>
    <div class="field">
        <span class="label">Aktiv:</span>
        <span class="value {{ $employee->is_active ? 'active-yes' : 'active-no' }}"> <!-- ✅ aktiv → is_active -->
            {{ $employee->is_active ? '✅ Aktiv' : '❌ Passiv' }}
        </span>
    </div>

    <div class="section-title">📝 Qeyd</div>
    <div class="field">
        <span class="label">Qeyd:</span>
        <span class="value">{{ $employee->notes ?? '-' }}</span> <!-- ✅ qeyd → notes -->
    </div>

    <div class="section-title">📅 Əlavə Məlumatlar</div>
    <div class="field">
        <span class="label">Yaradılma:</span>
        <span class="value">{{ $employee->created_at ? $employee->created_at->format('d.m.Y H:i') : '-' }}</span>
    </div>
    <div class="field">
        <span class="label">Son Yenilənmə:</span>
        <span class="value">{{ $employee->updated_at ? $employee->updated_at->format('d.m.Y H:i') : '-' }}</span>
    </div>

    <br>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Redaktə Et
        </a>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>
</div>
@endsection
