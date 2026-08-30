@extends('layouts.app')

@section('title', 'Avtobus Məlumatları')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>🚌 Avtobus Məlumatları</h1>
        <a href="{{ route('buses.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>

    <!-- ========================================== -->
    <!-- ƏSAS MƏLUMATLAR                            -->
    <!-- ========================================== -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">BUS PROJECT</small>
                        <strong>{{ $bus->bus_project ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">VIN (Şassi №)</small>
                        <strong>{{ $bus->vin ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">UZUNLUQ</small>
                        <strong>{{ $bus->uzunluq ? number_format($bus->uzunluq, 1) . ' m' : '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Xətt №</small>
                        <strong>{{ $bus->xett_no ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">DQN</small>
                        <strong>{{ $bus->dqn }}</strong>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">MOTOR №</small>
                        <strong>{{ $bus->motor_no ?? '-' }}</strong>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block">Status</small>
                        <strong>
                            @if($bus->aktiv)
                                <span class="badge bg-success">✅ Aktiv</span>
                            @else
                                <span class="badge bg-danger">❌ Passiv</span>
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 📊 KM TARİXÇƏSİ                             -->
    <!-- ========================================== -->
    <div class="section-title mt-4">
        📊 KM Tarixçəsi
        <span class="badge bg-primary ms-2">{{ $bus->dailyKmRecords()->count() }} qeyd</span>
        <div class="float-end">
            <a href="{{ route('daily-km-records.create') }}?bus_id={{ $bus->id }}" class="btn btn-sm btn-success ms-2">
                <i class="bi bi-plus-lg"></i> KM Əlavə Et
            </a>
            <a href="{{ route('daily-km-records.import') }}" class="btn btn-sm btn-info ms-1">
                <i class="bi bi-upload"></i> Excel - dən Yüklə
            </a>
        </div>
    </div>

    <!-- Tarixə görə AXTARIŞ -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label fw-bold">📅 Tarix</label>
                    <input type="date" class="form-control" id="tarixFilter" onchange="filterKm()">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">📊 KM Aralığı</label>
                    <div class="d-flex gap-2">
                        <input type="number" class="form-control" id="kmMin" placeholder="Min KM" oninput="filterKm()">
                        <span class="align-self-center">-</span>
                        <input type="number" class="form-control" id="kmMax" placeholder="Max KM" oninput="filterKm()">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-secondary w-100" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise"></i> Sıfırla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Nəticələr -->
    @php
        $dailyKms = $bus->dailyKmRecords()->orderBy('tarix', 'desc')->get();
    @endphp

    @if($dailyKms->count() > 0)
        <div class="card">
            <div class="card-body">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover table-striped" id="kmTable">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>#</th>
                                <th>📅 Tarix</th>
                                <th>📊 KM</th>
                                <th>Əməliyyatlar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyKms as $index => $item)
                            <tr class="km-row" data-tarix="{{ $item->tarix ? $item->tarix->format('Y-m-d') : '' }}" data-km="{{ $item->km ?? 0 }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->tarix ? $item->tarix->format('d.m.Y') : '-' }}</td>
                                <td><strong>{{ $item->km ? number_format($item->km, 0, ',', '.') . ' km' : '-' }}</strong></td>
                                <td>
                                    <a href="{{ route('daily-km-records.show', $item) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('daily-km-records.edit', $item) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('daily-km-records.destroy', $item) }}" method="POST" style="display:inline" onsubmit="return confirm('Əminsən?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 text-muted">
                    <small>Cəmi: <strong>{{ $dailyKms->count() }}</strong> qeyd</small>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-graph-up" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                Hələ bu avtobus üçün KM məlumatı yoxdur.
                <br>
                <a href="{{ route('daily-km-records.create') }}?bus_id={{ $bus->id }}" class="btn btn-success btn-sm mt-2">
                    <i class="bi bi-plus-lg"></i> İlk KM - nı əlavə et
                </a>
                <a href="{{ route('daily-km-records.import') }}" class="btn btn-info btn-sm mt-2">
                    <i class="bi bi-upload"></i> Excel - dən yüklə
                </a>
            </div>
        </div>
    @endif

    <br>
    <a href="{{ route('buses.index') }}" class="btn btn-secondary">⬅ Geri</a>
</div>
@endsection

@section('scripts')
<script>
    function filterKm() {
        const tarix = document.getElementById('tarixFilter').value;
        const kmMin = document.getElementById('kmMin').value;
        const kmMax = document.getElementById('kmMax').value;

        const rows = document.querySelectorAll('#kmTable tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 3) return;

            // Cədvəldəki tarix "30.07.2026" formatındadır
            const rowTarixText = cells[1]?.textContent?.trim() || '';
            // Inputdan gələn tarix "2026-07-30" formatındadır
            const inputTarix = tarix ? tarix.split('-').reverse().join('.') : '';

            const rowKmText = cells[2]?.textContent?.trim() || '';
            const rowKm = parseInt(rowKmText.replace(/[^0-9]/g, '')) || 0;

            let show = true;

            // Tarix filtrini düzgün formatda yoxla
            if (tarix && rowTarixText !== inputTarix) {
                show = false;
            }
            if (kmMin && rowKm < parseInt(kmMin)) {
                show = false;
            }
            if (kmMax && rowKm > parseInt(kmMax)) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    function resetFilters() {
        document.getElementById('tarixFilter').value = '';
        document.getElementById('kmMin').value = '';
        document.getElementById('kmMax').value = '';
        filterKm();
    }
</script>
@endsection
