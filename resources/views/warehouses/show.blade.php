@extends('layouts.app')

@section('title', 'Warehouse Item Details')

@section('content')
<div class="container">
    <h1>📦 Warehouse Item Details</h1>

    <div class="section-title">📋 Basic Information</div>
    <div class="field">
        <span class="label">ID:</span>
        <span class="value">{{ $warehouse->id }}</span>
    </div>
    <div class="field">
        <span class="label">Code:</span>
        <span class="value"><strong>{{ $warehouse->code }}</strong></span>
    </div>
    <div class="field">
        <span class="label">Name:</span>
        <span class="value">{{ $warehouse->name }}</span>
    </div>
    <div class="field">
        <span class="label">Category:</span>
        <span class="value">{{ $warehouse->category ?? '-' }}</span>
    </div>
    <div class="field">
        <span class="label">Unit:</span>
        <span class="value">{{ $warehouse->unit ?? '-' }}</span>
    </div>

    <div class="section-title">📊 Stock Information</div>
    <div class="field">
        <span class="label">Quantity:</span>
        <span class="value">
            <strong>{{ $warehouse->quantity }}</strong>
            @if($warehouse->quantity <= 0)
                <span class="status-empty">🔴 Out of Stock</span>
            @elseif($warehouse->quantity <= $warehouse->minimum_quantity)
                <span class="status-low">🟡 Low Stock</span>
            @else
                <span class="status-good">🟢 Normal</span>
            @endif
        </span>
    </div>
    <div class="field">
        <span class="label">Minimum Quantity:</span>
        <span class="value">{{ $warehouse->minimum_quantity }}</span>
    </div>
    <div class="field">
        <span class="label">Unit Price:</span>
        <span class="value">{{ $warehouse->price ? number_format($warehouse->price, 2) . ' ₼' : '-' }}</span>
    </div>
    <div class="field">
        <span class="label">Total Price:</span>
        <span class="value">
            <strong>{{ $warehouse->price ? number_format($warehouse->quantity * $warehouse->price, 2) . ' ₼' : '-' }}</strong>
        </span>
    </div>

    <div class="section-title">🏢 Supplier</div>
    <div class="field">
        <span class="label">Supplier:</span>
        <span class="value">{{ $warehouse->supplier ?? '-' }}</span>
    </div>

    <div class="section-title">📝 Notes</div>
    <div class="field">
        <span class="label">Notes:</span>
        <span class="value">{{ $warehouse->notes ?? '-' }}</span>
    </div>

    <div class="field">
        <span class="label">Created:</span>
        <span class="value">{{ $warehouse->created_at }}</span>
    </div>
    <div class="field">
        <span class="label">Updated:</span>
        <span class="value">{{ $warehouse->updated_at }}</span>
    </div>

    <br>
    @if(Auth::user()->hasGarageRole(['admin', 'warehouse']))
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-warning">✏️ Edit</a>
    @endif
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">⬅ Back</a>
</div>
@endsection