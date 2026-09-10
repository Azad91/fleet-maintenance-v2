<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>BUS PROJECT</th>
                        <th>VIN</th>
                        <th>UZUNLUQ</th>
                        <th>Route No</th>
                        <th>DQN</th>
                        <th>ENGINE No</th>
                        <th style="width: 150px; text-align: center;">📊 Latest KM</th>
                        <th style="width: 150px; text-align: center;">Actions</th>
                    </tr>
                    <tr id="busTableFilter" style="background-color: #f8f9fa;">
                        <th></th>
                        <th></th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="bus_project"
                                placeholder="🔍 Project..." style="font-size: 13px;"
                                value="{{ request('bus_project') }}" autocomplete="off">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="vin"
                                placeholder="🔍 Chassis..." style="font-size: 13px;"
                                value="{{ request('vin') }}" autocomplete="off">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="uzunluq"
                                placeholder="🔍 Length..." style="font-size: 13px;"
                                value="{{ request('uzunluq') }}" autocomplete="off">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="route_number"
                                placeholder="🔍 Route..." style="font-size: 13px;"
                                value="{{ request('route_number') }}" autocomplete="off">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="dqn"
                                placeholder="🔍 DQN..." style="font-size: 13px;"
                                value="{{ request('dqn') }}" autocomplete="off">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="engine_number"
                                placeholder="🔍 Engine..." style="font-size: 13px;"
                                value="{{ request('engine_number') }}" autocomplete="off">
                        </th>
                        <th style="text-align: center;"></th>
                        <th style="text-align: center;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buses as $bus)
                    <tr>
                        <td>
                            <input type="checkbox" class="bus-checkbox" value="{{ $bus->id }}">
                        </td>
                        <td style="text-align: center;">{{ $buses->firstItem() + $loop->index }}</td>
                        <td>{{ $bus->bus_project ?? '-' }}</td>
                        <td>{{ $bus->vin ?? '-' }}</td>
                        <td>{{ $bus->uzunluq ? number_format($bus->uzunluq, 1) . ' m' : '-' }}</td>
                        <td>{{ $bus->route_number ?? '-' }}</td>
                        <td><strong>{{ $bus->dqn }}</strong></td>
                        <td>{{ $bus->engine_number ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($bus->latestKmRecord)
                                <strong>{{ number_format($bus->latestKmRecord->km, 0, ',', '.') }} km</strong>
                                <br>
                                <small class="text-muted">{{ $bus->latestKmRecord->date->format('d.m.Y') }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('buses.show', $bus) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(Auth::user()->hasGarageRole('admin'))
                                    <a href="{{ route('buses.edit', $bus) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('buses.destroy', $bus) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-bus-front" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            @if($hasActiveFilters ?? false)
                                <p class="mb-2">No results found for the given filters.</p>
                                <a href="{{ route('buses.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Clear filters
                                </a>
                            @else
                                <p class="mb-0">No buses yet. <a href="{{ route('buses.import') }}">Import from Excel!</a></p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ✅ Pagination indi partial içindədir --}}
        @if($buses->hasPages())
            <div class="pagination-wrapper d-flex justify-content-center mt-4">
                {{ $buses->withQueryString()->links() }}
            </div>
        @endif

        @if($buses->total() > 0)
            <div class="text-center text-muted small mt-2">
                Showing {{ $buses->firstItem() }}–{{ $buses->lastItem() }} of {{ $buses->total() }} buses
            </div>
        @endif
    </div>
</div>
