@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><span class="fleet-eyebrow">ADMINISTRATION</span><h1 class="mb-0">{{ $user->name }}</h1></div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        @include('users.partials.form', ['submitLabel' => 'Save Changes'])
    </div>
</div>
@endsection