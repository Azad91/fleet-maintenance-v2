@extends('layouts.app')

@section('title', 'Bus Details')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3>Bus Details - #{{ $bus->id }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr><th>Project</th><td>{{ $bus->bus_project }}</td></tr>
                                <tr><th>VIN</th><td>{{ $bus->vin }}</td></tr>
                                <tr><th>Length</th><td>{{ $bus->uzunluq }}</td></tr>
                                <tr><th>Route No</th><td>{{ $bus->route_number }}</td></tr>
                                <tr><th>DQN</th><td>{{ $bus->dqn }}</td></tr>
                                <tr><th>Engine No</th><td>{{ $bus->engine_number }}</td></tr>
                                <tr><th>KM</th><td>{{ $bus->km }}</td></tr>
                                <tr><th>Date</th><td>{{ $bus->date ? $bus->date->format('d.m.Y') : '' }}</td></tr>
                                <tr><th>Status</th><td>{{ $bus->is_active ? 'Active' : 'Inactive' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily KM Records -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Daily KM Records</h4>
                </div>
                <div class="card-body">
                    @php
                        $dailyKms = $bus->dailyKmRecords()->orderBy('date', 'desc')->get();
                    @endphp

                    @if($dailyKms->count() > 0)
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-striped" id="kmTable">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>KM</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyKms as $index => $record)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $record->date ? $record->date->format('d.m.Y') : '' }}</td>
                                            <td>{{ $record->km }}</td>
                                            <td>{{ $record->notes }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No KM records found for this bus.</p>
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
        const date = document.getElementById('tarixFilter').value;
        const kmMin = document.getElementById('kmMin').value;
        const kmMax = document.getElementById('kmMax').value;

        const rows = document.querySelectorAll('#kmTable tbody tr');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 3) return;

            const rowDateText = cells[1]?.textContent?.trim() || '';
            const inputDate = date ? date.split('-').reverse().join('.') : '';

            const rowKmText = cells[2]?.textContent?.trim() || '';
            const rowKm = parseInt(rowKmText.replace(/[^0-9]/g, '')) || 0;

            let show = true;

            if (date && rowDateText !== inputDate) {
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