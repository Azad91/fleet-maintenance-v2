@extends('layouts.app')

@section('title', __('messages.profile.title'))

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="mb-4">👤 {{ __('messages.profile.title') }}</h1>
    </div>
</div>

@if(session('status') === 'profile-updated')
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i> {{ __('messages.profile.updated_status') }}
    </div>
@endif

@if(session('status') === 'password-updated')
    <div class="fleet-alert fleet-alert--success">
        <i class="fas fa-circle-check"></i> {{ __('messages.profile.password_updated_status') }}
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.profile.info') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">{{ __('messages.auth.full_name') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                        </div>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">{{ __('messages.auth.email') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                        </div>
                        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label fw-bold">{{ __('messages.users.role') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="text" class="form-control" id="role" value="{{ auth()->user()->getCurrentGarageRole() ?? auth()->user()->role }}" disabled style="background:#e9ecef;">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> {{ __('messages.common.update') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.profile.change_password') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-bold">{{ __('messages.profile.current_password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        @error('current_password', 'updatePassword')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold">{{ __('messages.profile.new_password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                        </div>
                        @error('password', 'updatePassword')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-bold">{{ __('messages.profile.confirm_password') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key"></i> {{ __('messages.profile.update_password') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection