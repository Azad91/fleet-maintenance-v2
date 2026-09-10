@extends('layouts.app')

@section('title', __('messages.employees.new'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>➕ {{ __('messages.employees.new') }}</h4>
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

        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="first_name" class="form-label fw-bold">{{ __('messages.employees.first_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name" required
                       placeholder="{{ __('messages.employees.first_name_placeholder') }}"
                       value="{{ old('first_name') }}">
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label fw-bold">{{ __('messages.employees.last_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="last_name" name="last_name" required
                       placeholder="{{ __('messages.employees.last_name_placeholder') }}"
                       value="{{ old('last_name') }}">
            </div>

            <div class="mb-3">
                <label for="position" class="form-label fw-bold">{{ __('messages.employees.position') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="position" name="position" required>
                    <option value="">{{ __('messages.employees.select_position') }}</option>
                    @foreach($positions as $key => $label)
                        <option value="{{ $key }}" {{ old('position') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label fw-bold">📝 {{ __('messages.common.notes') }}</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"
                          placeholder="{{ __('messages.employees.notes_placeholder') }}">{{ old('notes') }}</textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">✅ {{ __('messages.common.active') }}</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> {{ __('messages.common.save') }}
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection