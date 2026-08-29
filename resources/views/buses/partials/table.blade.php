<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">🚌 Avtobuslar</h5>
            <span class="badge bg-primary rounded-pill">
                Cəmi: {{ $buses->count() }} ədəd
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px; text-align: center;">№</th>
                        <th>BUS PROJECT</th>
                        <th>VIN</th>
                        <th>UZUNLUQ</th>
                        <th>Xətt №</th>
                        <th>DQN</th>
                        <th>MOTOR №</th>
                        <th style="width: 150px; text-align: center;">📊 Son KM</th>
                        <th style="width: 150px; text-align: center;">Əməliyyatlar</th>
                    </tr>
                    <tr id="busTableFilter" style="background-color: #f8f9fa;">
                        <th></th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="bus_project"
                                placeholder="🔍 Layihə..." style="font-size: 13px;">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="vin"
                                placeholder="🔍 Şassi..." style="font-size: 13px;">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="uzunluq"
                                placeholder="🔍 Uzunluq..." style="font-size: 13px;">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="xett_no"
                                placeholder="🔍 Xətt..." style="font-size: 13px;">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="dqn"
                                placeholder="🔍 DQN..." style="font-size: 13px;">
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" name="motor_no"
                                placeholder="🔍 Motor..." style="font-size: 13px;">
                        </th>
                        <th style="text-align: center;"></th>
                        <th style="text-align: center;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($buses as $bus)
                    <tr>
                        <td style="text-align: center;">{{ $buses->firstItem() + $loop->index }}</td>
                        <td>{{ $bus->bus_project ?? '-' }}</td>
                        <td>{{ $bus->vin ?? '-' }}</td>
                        <td>{{ $bus->uzunluq ? number_format($bus->uzunluq, 1) . ' m' : '-' }}</td>
                        <td>{{ $bus->xett_no ?? '-' }}</td>
                        <td><strong>{{ $bus->dqn }}</strong></td>
                        <td>{{ $bus->motor_no ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($bus->latestKmRecord)
                                <strong>{{ number_format($bus->latestKmRecord->km, 0, ',', '.') }} km</strong>
                                <br>
                                <small class="text-muted">{{ $bus->latestKmRecord->tarix->format('d.m.Y') }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('buses.show', $bus) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(Auth::user()->role == 'admin')
                                    <a href="{{ route('buses.edit', $bus) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('buses.destroy', $bus) }}" method="POST" style="display:inline" onsubmit="return confirm('Əminsən?')">
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
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-bus-front" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            @if(isset($isEmpty) && $isEmpty)
                                <p class="mb-0">Axtarış nəticəsində heç nə tapılmadı.</p>
                            @else
                                <p class="mb-0">Hələ avtobus yoxdur. <a href="{{ route('buses.import') }}">Excel - dən yüklə!</a></p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
