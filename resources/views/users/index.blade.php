@extends('layouts.app')

@section('title', __('messages.users.title'))

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="fleet-eyebrow">{{ __('messages.nav.administration') }}</span>
        <h1 class="mb-1">{{ __('messages.users.title') }}</h1>
        <p class="text-muted mb-0">{{ __('messages.users.manage_desc') }}</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> {{ __('messages.users.new') }}
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.users.table_user') }}</th>
                        <th>{{ __('messages.users.table_email') }}</th>
                        <th>{{ __('messages.users.table_role') }}</th>
                        <th>{{ __('messages.users.table_status') }}</th>
                        <th class="text-end">{{ __('messages.users.table_action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php($garageRole = $user->garages->first()?->pivot)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if($user->is(auth()->user()))
                                    <span class="badge text-bg-primary ms-1">{{ __('messages.users.you') }}</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $garageRole?->role ? __('roles.' . $garageRole->role) : '-' }}
                                </span>
                            </td>
                            <td>
                                @if($garageRole?->is_active)
                                    <span class="badge text-bg-success">{{ __('messages.common.active') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ __('messages.common.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> {{ __('messages.common.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                {{ __('messages.users.no_users') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $users->links() }}</div>
@endsection