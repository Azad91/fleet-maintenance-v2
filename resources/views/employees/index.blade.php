@extends('layouts.app')

@section('title', __('messages.employees.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>👥 {{ __('messages.employees.title') }}</h1>
    <div>
        <a href="{{ route('employees.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> {{ __('messages.employees.import') }}
        </a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('messages.employees.new') }}
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('messages.employees.full_name') }}</th>
                        <th>{{ __('messages.employees.position') }}</th>
                        <th>{{ __('messages.common.status') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td><strong>{{ $employee->full_name }}</strong></td>
                        <td>{{ $employee->position }}</td>
                        <td>
                            <span class="badge-status {{ $employee->is_active ? 'aktiv' : 'passiv' }}">
                                {{ $employee->is_active ? '✅ ' . __('messages.common.active') : '❌ ' . __('messages.common.inactive') }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('messages.common.confirm') }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-people" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            {{ __('messages.employees.no_employees') }}
                            <a href="{{ route('employees.create') }}">{{ __('messages.employees.new') }}</a>
                            {{ __('messages.common.or') }}
                            <a href="{{ route('employees.import') }}">{{ __('messages.employees.import') }}</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $employees->withQueryString()->links() }}
</div>
@endsection