@extends('layouts.app')

@section('title', 'Gündəlik Avtobus Statusları')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>📋 Gündəlik Avtobus Statusları</h1>
    <div>
        <a href="{{ route('bus-daily-statuses.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel - dən Yüklə
        </a>
        <a href="{{ route('bus-daily-statuses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Yeni Status
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>№</th>
                        <th>Avtobus (DQN)</th>
                        <th>Xətt №</th>
                        <th>Tarix</th>
                        <th>Status</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statuses as $status)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $status->bus->dqn ?? '-' }}</strong></td>
                        <td>{{ $status->bus->xett_no ?? '-' }}</td>
                        <td>{{ $status->tarix ? \Carbon\Carbon::parse($status->tarix)->format('d.m.Y') : '-' }}</td>
                        <td>
                            @php
                                $bgClass = match(trim($status->status)) {
                                    'XƏTTƏ ÇIXMAĞA UYĞUN' => 'bg-success text-white',
                                    'İSTİSMARA YARARSIZ(EHTİYYAT HİSSƏ)' => 'bg-danger text-white',
                                    'İSTİSMARA YARARSIZ(NASAZLIQ)' => 'bg-danger text-white',
                                    'QƏZALI' => 'bg-warning text-dark',
                                    default => 'bg-secondary text-white'
                                };
                            @endphp
                            <span class="badge {{ $bgClass }}" style="font-size: 14px; padding: 8px 14px; border-radius: 6px;">
                                {{ $status->status }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('bus-daily-statuses.show', $status) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('bus-daily-statuses.edit', $status) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-calendar2-week" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            Hələ status məlumatı yoxdur.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $statuses->withQueryString()->links() }}
</div>
@endsection
