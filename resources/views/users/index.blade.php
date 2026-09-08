@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">ADMINISTRATION</span>
        <h1 class="mb-1">Users</h1>
        <p class="text-muted mb-0">Manage access and permissions for the current garage.</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> New User
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php($garageRole = $user->garages->first()?->pivot)
                        <tr>
                            <td><strong>{{ $user->name }}</strong>@if($user->is(auth()->user())) <span class="badge text-bg-primary ms-1">You</span>@endif</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge text-bg-secondary">{{ match($garageRole?->role) { 'admin' => 'Admin', 'complaint' => 'Cards / Complaints', 'warehouse' => 'Warehouse', 'daily_km' => 'Daily KM', 'daily_status' => 'Daily Status', 'directorate' => 'Directorate', default => $garageRole?->role ?? '-' } }}</span></td>
                            <td>
                                @if($garageRole?->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end"><a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No users assigned to this garage yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $users->links() }}</div>
@endsection