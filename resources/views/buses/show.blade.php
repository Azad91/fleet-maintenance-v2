@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3>Avtobus məlumatları - #{{ $bus->id }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>Layihə</th><td>{{ $bus->bus_project }}</td></tr>
                                <tr><th>VIN</th><td>{{ $bus->vin }}</td></tr>
                                <tr><th>Uzunluq</th><td>{{ $bus->uzunluq }}</td></tr>
                                <tr><th>Xətt nömrəsi</th><td>{{ $bus->route_number }}</td></tr> <!-- ✅ -->
                                <tr><th>DQN</th><td>{{ $bus->dqn }}</td></tr>
                                <tr><th>Mühərrik nömrəsi</th><td>{{ $bus->engine_number }}</td></tr> <!-- ✅ -->
                                <tr><th>KM</th><td>{{ $bus->km }}</td></tr>
                                <tr><th>Tarix</th><td>{{ $bus->date ? $bus->date->format('d.m.Y') : '' }}</td></tr> <!-- ✅ -->
                                <tr><th>Status</th><td>{{ $bus->is_active ? 'Aktiv' : 'Qeyri-aktiv' }}</td></tr> <!-- ✅ -->
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gündəlik KM-lər -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Gündəlik KM qeydləri</h4>
                </div>
                <div class="card-body">
                    @php
                        $dailyKms = $bus->dailyKmRecords()->orderBy('date', 'desc')->get(); // ✅
                    @endphp

                    @if($dailyKms->count() > 0)
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-striped" id="kmTable">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>#</th>
                                        <th>Tarix</th>
                                        <th>KM</th>
                                        <th>Qeyd</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyKms as $index => $record)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $record->date ? $record->date->format('d.m.Y') : '' }}</td> <!-- ✅ -->
                                            <td>{{ $record->km }}</td>
                                            <td>{{ $record->notes }}</td> <!-- ✅ -->
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Bu avtobus üçün hələ KM qeydi yoxdur.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
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
