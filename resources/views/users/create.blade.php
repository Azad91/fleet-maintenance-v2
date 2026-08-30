@extends('layouts.app')

@section('title', 'Yeni istifadəçi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><span class="fleet-eyebrow">İDARƏETMƏ</span><h1 class="mb-0">Yeni istifadəçi yarat</h1></div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Geri</a>
</div>

<div class="card">
    <div class="card-body">
        @include('users.partials.form', ['submitLabel' => 'İstifadəçini yarat'])
    </div>
</div>
@endsection
