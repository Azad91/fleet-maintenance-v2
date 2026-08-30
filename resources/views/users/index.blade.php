@extends('layouts.app')

@section('title', 'İstifadəçilər')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">İDARƏETMƏ</span>
        <h1 class="mb-1">İstifadəçilər</h1>
        <p class="text-muted mb-0">Cari qaraj üçün giriş və əməliyyat icazələrini idarə edin.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Yeni istifadəçi
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>İstifadəçi</th>
                        <th>E-mail</th>
                        <th>Rol</th>
                        <th>Status</th>
                        <th class="text-end">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php($garageRole = $user->garages->first()?->pivot)
                        <tr>
                            <td><strong>{{ $user->name }}</strong>@if($user->is(auth()->user())) <span class="badge text-bg-primary ms-1">Siz</span>@endif</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge text-bg-secondary">{{ match($garageRole?->role) { 'admin' => 'Admin', 'complaint' => 'Kartlar / Şikayətlər', 'warehouse' => 'Anbar', 'daily_km' => 'Günlük KM', 'daily_status' => 'Günlük statuslar', 'directorate' => 'Müdiriyyət', default => $garageRole?->role ?? '-' } }}</span></td>
                            <td>
                                @if($garageRole?->is_active)
                                    <span class="badge text-bg-success">Aktiv</span>
                                @else
                                    <span class="badge text-bg-secondary">Passiv</span>
                                @endif
                            </td>
                            <td class="text-end"><a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Redaktə et</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">Bu qaraj üçün hələ istifadəçi təyin edilməyib.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $users->links() }}</div>
@endsection
