@extends('layouts.app')

@section('title', 'Anbar Məhsulu - Məlumatlar')

@section('content')
<div class="container">
    <h1>📦 Anbar Məhsulu - Məlumatlar</h1>

    <div class="section-title">📋 Əsas Məlumatlar</div>
    <div class="field">
        <span class="label">ID:</span>
        <span class="value">{{ $warehouse->id }}</span>
    </div>
    <div class="field">
        <span class="label">Kod:</span>
        <span class="value"><strong>{{ $warehouse->kod }}</strong></span>
    </div>
    <div class="field">
        <span class="label">Ad:</span>
        <span class="value">{{ $warehouse->ad }}</span>
    </div>
    <div class="field">
        <span class="label">Kateqoriya:</span>
        <span class="value">{{ $warehouse->kateqoriya ?? '-' }}</span>
    </div>
    <div class="field">
        <span class="label">Ölçü Vahidi:</span>
        <span class="value">{{ $warehouse->olcu_vahidi ?? '-' }}</span>
    </div>

    <div class="section-title">📊 Stok Məlumatları</div>
    <div class="field">
        <span class="label">Miqdar:</span>
        <span class="value">
            <strong>{{ $warehouse->miqdar }}</strong>
            @if($warehouse->miqdar <= 0)
                <span class="status-empty">🔴 Bitib</span>
            @elseif($warehouse->miqdar <= $warehouse->minimum_miqdar)
                <span class="status-low">🟡 Tükənir</span>
            @else
                <span class="status-good">🟢 Normal</span>
            @endif
        </span>
    </div>
    <div class="field">
        <span class="label">Minimum Miqdar:</span>
        <span class="value">{{ $warehouse->minimum_miqdar }}</span>
    </div>
    <div class="field">
        <span class="label">Vahid Qiyməti:</span>
        <span class="value">{{ $warehouse->qiymet ? number_format($warehouse->qiymet, 2) . ' ₼' : '-' }}</span>
    </div>
    <div class="field">
        <span class="label">Cəmi Qiymət:</span>
        <span class="value">
            <strong>{{ $warehouse->qiymet ? number_format($warehouse->miqdar * $warehouse->qiymet, 2) . ' ₼' : '-' }}</strong>
        </span>
    </div>

    <div class="section-title">🏢 Tədarükçü</div>
    <div class="field">
        <span class="label">Tədarükçü:</span>
        <span class="value">{{ $warehouse->tedarikci ?? '-' }}</span>
    </div>

    <div class="section-title">📝 Qeyd</div>
    <div class="field">
        <span class="label">Qeyd:</span>
        <span class="value">{{ $warehouse->qeyd ?? '-' }}</span>
    </div>

    <div class="field">
        <span class="label">Yaradılma:</span>
        <span class="value">{{ $warehouse->created_at }}</span>
    </div>
    <div class="field">
        <span class="label">Yenilənmə:</span>
        <span class="value">{{ $warehouse->updated_at }}</span>
    </div>

    <br>
    @if(Auth::user()->hasGarageRole(['admin', 'warehouse']))
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-warning">✏️ Redaktə Et</a>
    @endif
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">⬅ Geri</a>
</div>
@endsection
