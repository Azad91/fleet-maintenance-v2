@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Məlumatlar yadda saxlanmadı.</strong>
        <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@php($selectedRole = old('role', isset($garageRole) ? $garageRole->role : ''))

<form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @isset($user) @method('PUT') @endisset

    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label fw-bold">Ad soyad</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name ?? '') }}" required autofocus>
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label fw-bold">E-mail</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label for="role" class="form-label fw-bold">Cari qarajdakı rol</label>
            <select id="role" name="role" class="form-select" required>
                <option value="" disabled @selected($selectedRole === '')>Rol seçin...</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">Müdiriyyət rolu bütün səhifələrə baxa bilər, amma dəyişiklik və silmə edə bilməz.</small>
        </div>
        <div class="col-md-6">
            <label for="password" class="form-label fw-bold">{{ isset($user) ? 'Yeni şifrə' : 'Şifrə' }}</label>
            <input id="password" name="password" type="password" class="form-control" {{ isset($user) ? '' : 'required' }} autocomplete="new-password">
            <small class="form-text text-muted">@if(isset($user)) Dəyişməyəcəksinizsə boş saxlayın. @else Ən azı 8 simvol. @endif</small>
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label fw-bold">Şifrə təkrarı</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" {{ isset($user) ? '' : 'required' }} autocomplete="new-password">
        </div>
        @isset($user)
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" value="1" type="checkbox" class="form-check-input" @checked(old('is_active', $garageRole->is_active))>
                    <label for="is_active" class="form-check-label">Bu qaraj üçün hesab aktivdir</label>
                </div>
            </div>
        @endisset
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> {{ $submitLabel }}</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Ləğv et</a>
    </div>
</form>
