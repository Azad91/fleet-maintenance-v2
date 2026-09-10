@extends('layouts.app')

@section('title', __('messages.complaint_types.new'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ {{ __('messages.complaint_types.new') }}</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('complaint-types.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">{{ __('messages.complaint_types.name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required
                       placeholder="{{ __('messages.complaint_types.name_placeholder') }}"
                       value="{{ old('name') }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('complaint-types.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection