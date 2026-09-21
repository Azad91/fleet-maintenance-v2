@php
    use App\Enums\OilType;
@endphp

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>{{ __('messages.oil_change.bus') }}</th>
                        <th>{{ __('messages.oil_change.last_change_km') }}</th>
                        <th>{{ __('messages.oil_change.current_km') }}</th>
                        <th>{{ __('messages.oil_change.next_due_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.remaining_km') }}</th>
                        <th>{{ __('messages.oil_change.column_status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statuses as $status)
                        @php
                            $remaining = $status->remainingKm;
                            $overdueBy = $status->overdueByKm();
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('oil-changes.show', $status->bus) }}"
                                   class="text-decoration-none">
                                    <strong>{{ $status->bus->dqn }}</strong>
                                </a>
                                @if($status->bus->route_number)
                                    <br>
                                    <small class="text-muted">
                                        {{ __('messages.daily_km.route_label', ['route' => $status->bus->route_number]) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($status->lastChange)
                                    <strong>{{ number_format($status->lastChange->actual_km, 0, '', '.') }}</strong> km
                                    @if($status->lastChange->changed_at)
                                        <br>
                                        <small class="text-muted">
                                            {{ $status->lastChange->changed_at->format('d.m.Y') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">{{ __('messages.oil_change.no_history') }}</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ number_format($status->currentKm, 0, '', '.') }}</strong> km
                            </td>
                            <td>
                                @if($status->nextDueKm)
                                    {{ number_format($status->nextDueKm, 0, '', '.') }} km
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($remaining === null)
                                    <span class="text-muted">—</span>
                                @elseif($status->isOverdue())
                                    <span class="text-danger fw-bold">
                                        −{{ number_format($overdueBy, 0, '', '.') }} km
                                    </span>
                                @else
                                    <strong>{{ number_format($remaining, 0, '', '.') }}</strong> km
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $status->bootstrapColor() }}">
                                    {{ $status->statusLabel() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    @if($status->lastChange)
                                        <a href="{{ route('oil-changes.edit', $status->lastChange) }}"
                                           class="btn btn-sm btn-outline-warning"
                                           title="{{ __('messages.common.edit') }}">
                                            <i class="fas fa-pencil"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('oil-changes.create', ['bus_id' => $status->bus->id, 'type' => $status->type->value]) }}"
                                       class="btn btn-sm btn-outline-success"
                                       title="{{ __('messages.oil_change.add_change') }}">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-oil-can fa-2x mb-3 d-block" style="opacity: .3;"></i>
                                {{ __('messages.oil_change.no_priority_items') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
