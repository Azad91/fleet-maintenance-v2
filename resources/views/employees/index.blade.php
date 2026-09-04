@extends('layouts.app')

@section('title', 'İşçilər')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>👥 İşçilər</h1>
    <div>
        <a href="{{ route('employees.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Yeni İşçi
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
                        <th>Ad Soyad</th>
                        <th>Vəzifə</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employee->id }}</td>
                        <td><strong>{{ $employee->full_name }}</strong></td>
                        <td>{{ $employee->position }}</td> <!-- ✅ vezifesi → position -->
                        <td>
                            <span class="badge-status {{ $employee->is_active ? 'aktiv' : 'passiv' }}"> <!-- ✅ aktiv → is_active -->
                                {{ $employee->is_active ? '✅ Aktiv' : '❌ Passiv' }}
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
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Əminsən?')">
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
                            Hələ işçi yoxdur.
                            <a href="{{ route('employees.create') }}">Yenisini əlavə et!</a>
                            və ya
                            <a href="{{ route('employees.import') }}">Excel - dən yüklə!</a>
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
