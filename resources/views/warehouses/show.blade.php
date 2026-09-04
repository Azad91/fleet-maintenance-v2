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
        <span class="value"><strong>{{ $warehouse->code }}</strong></span> <!-- ✅ kod → code -->
    </div>
    <div class="field">
        <span class="label">Ad:</span>
        <span class="value">{{ $warehouse->name }}</span> <!-- ✅ ad → name -->
    </div>
    <div class="field">
        <span class="label">Kateqoriya:</span>
        <span class="value">{{ $warehouse->category ?? '-' }}</span> <!-- ✅ kateqoriya → category -->
    </div>
    <div class="field">
        <span class="label">Ölçü Vahidi:</span>
        <span class="value">{{ $warehouse->unit ?? '-' }}</span> <!-- ✅ olcu_vahidi → unit -->
    </div>

    <div class="section-title">📊 Stok Məlumatları</div>
    <div class="field">
        <span class="label">Miqdar:</span>
        <span class="value">
            <strong>{{ $warehouse->quantity }}</strong> <!-- ✅ miqdar → quantity -->
            @if($warehouse->quantity <= 0)
                <span class="status-empty">🔴 Bitib</span>
            @elseif($warehouse->quantity <= $warehouse->minimum_quantity)
                <span class="status-low">🟡 Tükənir</span>
            @else
                <span class="status-good">🟢 Normal</span>
            @endif
        </span>
    </div>
    <div class="field">
        <span class="label">Minimum Miqdar:</span>
        <span class="value">{{ $warehouse->minimum_quantity }}</span> <!-- ✅ minimum_miqdar → minimum_quantity -->
    </div>
    <div class="field">
        <span class="label">Vahid Qiyməti:</span>
        <span class="value">{{ $warehouse->price ? number_format($warehouse->price, 2) . ' ₼' : '-' }}</span> <!-- ✅ qiymet → price -->
    </div>
    <div class="field">
        <span class="label">Cəmi Qiymət:</span>
        <span class="value">
            <strong>{{ $warehouse->price ? number_format($warehouse->quantity * $warehouse->price, 2) . ' ₼' : '-' }}</strong>
        </span>
    </div>

    <div class="section-title">🏢 Tədarükçü</div>
    <div class="field">
        <span class="label">Tədarükçü:</span>
        <span class="value">{{ $warehouse->supplier ?? '-' }}</span> <!-- ✅ tedarikci → supplier -->
    </div>

    <div class="section-title">📝 Qeyd</div>
    <div class="field">
        <span class="label">Qeyd:</span>
        <span class="value">{{ $warehouse->notes ?? '-' }}</span> <!-- ✅ qeyd → notes -->
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
