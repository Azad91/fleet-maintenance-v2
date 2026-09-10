@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ __('messages.users.not_saved') }}</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php($selectedRole = old('role', isset($garageRole) ? $garageRole->role : ''))

<form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @isset($user) @method('PUT') @endisset

    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label fw-bold">{{ __('messages.users.full_name') }}</label>
            <input id="name" name="name" type="text" class="form-control"
                   value="{{ old('name', $user->name ?? '') }}" required autofocus>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label fw-bold">{{ __('messages.users.email') }}</label>
            <input id="email" name="email" type="email" class="form-control"
                   value="{{ old('email', $user->email ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label for="role" class="form-label fw-bold">{{ __('messages.users.role') }}</label>
            <select id="role" name="role" class="form-select" required>
                <option value="" disabled @selected($selectedRole === '')>{{ __('messages.users.select_role') }}</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">{{ __('messages.users.role_hint') }}</small>
        </div>
        <div class="col-md-6">
            <label for="password" class="form-label fw-bold">
                {{ isset($user) ? __('messages.users.new_password') : __('messages.users.password') }}
            </label>
            <input id="password" name="password" type="password" class="form-control"
                   {{ isset($user) ? '' : 'required' }} autocomplete="new-password">
            <small class="form-text text-muted">
                @if(isset($user))
                    {{ __('messages.users.password_optional') }}
                @else
                    {{ __('messages.users.password_min') }}
                @endif
            </small>
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label fw-bold">{{ __('messages.users.password_confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
                   {{ isset($user) ? '' : 'required' }} autocomplete="new-password">
        </div>
        @isset($user)
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" value="1" type="checkbox" class="form-check-input"
                           @checked(old('is_active', $garageRole->is_active))>
                    <label for="is_active" class="form-check-label">{{ __('messages.users.is_active') }}</label>
                </div>
            </div>
        @endisset
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-save"></i> {{ $submitLabel }}
        </button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('messages.common.cancel') }}</a>
    </div>
</form>