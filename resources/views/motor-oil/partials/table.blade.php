@if(isset($search) && $search && $grouped->count() == 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        {{ __('messages.motor_oil.no_results', ['search' => $search]) }}
    </div>
@endif

@forelse($grouped as $km => $items)
    @php
        $items = $items->sortBy('part_name');
    @endphp
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="fw-bold text-primary">
                📍 {{ number_format($km, 0, ',', '.') }} KM
                <span class="badge bg-secondary">{{ __('messages.motor_oil.parts_count', ['count' => $items->count()]) }}</span>
            </h4>
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 60px; text-align: center;">#</th>
                            <th style="width: 150px;">{{ __('messages.motor_oil.part_code') }}</th>
                            <th>{{ __('messages.motor_oil.part_name') }}</th>
                            <th style="width: 100px; text-align: center;">{{ __('messages.motor_oil.unit') }}</th>
                            <th style="width: 100px; text-align: center;">{{ __('messages.motor_oil.quantity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td><strong>{{ $item->part_code }}</strong></td>
                            <td>{{ $item->part_name }}</td>
                            <td style="text-align: center;">{{ $item->unit ?? '-' }}</td>
                            <td style="text-align: center;">{{ number_format($item->quantity, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-4">
        <i class="bi bi-inbox" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
        {{ __('messages.motor_oil.no_details') }}
    </div>
@endforelse

<span class="total-count d-none" data-count="{{ $grouped->sum(function($items) { return $items->count(); }) }}"></span>