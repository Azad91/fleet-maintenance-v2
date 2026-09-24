@php
    use App\Enums\OilType;

    // ─── Query-string carried through tab and chip links ───
    // Ensures that a middle-click or a JS-disabled click still
    // preserves the current DQN / route / KM filters.
    $queryParams = array_filter([
        'dqn'          => $filters['dqn'] ?? '',
        'route_number' => $filters['route_number'] ?? '',
        'km_min'       => $filters['km_min'] ?? null,
        'km_max'       => $filters['km_max'] ?? null,
    ], fn ($v) => $v !== null && $v !== '');

    $hasActiveFilters = ! empty($queryParams);

    $statusChips = [
        'all'        => ['label' => __('messages.oil_change.all_statuses'),      'color' => 'secondary'],
        'overdue'    => ['label' => __('messages.oil_change.status.overdue'),    'color' => 'danger'],
        'critical'   => ['label' => __('messages.oil_change.status.critical'),   'color' => 'warning'],
        'due-soon'   => ['label' => __('messages.oil_change.status.due-soon'),   'color' => 'info'],
        'ok'         => ['label' => __('messages.oil_change.status.ok'),         'color' => 'success'],
        'no-history' => ['label' => __('messages.oil_change.status.no-history'), 'color' => 'secondary'],
    ];
@endphp

{{-- ─── Status filter chips ─── --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($statusChips as $key => $chip)
        @php $isActive = $statusFilter === $key; @endphp
        <a href="{{ route('oil-changes.index', array_merge($queryParams, ['type' => $activeType->value, 'status' => $key])) }}"
           class="btn btn-sm oil-status-chip {{ $isActive ? 'btn-' . $chip['color'] : 'btn-outline-' . $chip['color'] }}"
           data-status="{{ $key }}">
            {{ $chip['label'] }}
        </a>
    @endforeach
</div>

{{-- ─── Tabs ─── --}}
<ul class="nav nav-tabs mb-0">
    @foreach(OilType::cases() as $type)
        @php
            $isActiveTab = $activeType === $type;
            $count = $typeCounts[$type->value] ?? 0;
        @endphp
        <li class="nav-item">
            <a class="nav-link oil-tab-link {{ $isActiveTab ? 'active' : '' }}"
               href="{{ route('oil-changes.index', array_merge($queryParams, ['type' => $type->value, 'status' => $statusFilter])) }}"
               data-type="{{ $type->value }}">
                {{ $type->icon() }} {{ $type->label() }}
                <span class="badge bg-secondary ms-1">{{ $count }}</span>
            </a>
        </li>
    @endforeach
</ul>

{{-- ─── Active tab content ─── --}}
<div class="card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong style="font-size: 15px;">
                {{ $activeType->icon() }} {{ $activeType->label() }}
            </strong>
            <span class="badge bg-secondary ms-2">{{ $rows->count() }}</span>
            @if($urgentCount > 0)
                <a href="{{ route('oil-changes.urgent') }}" class="badge bg-danger text-decoration-none ms-1">
                    <i class="fas fa-triangle-exclamation"></i> {{ $urgentCount }} {{ __('messages.oil_change.urgent_short') }}
                </a>
            @endif
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.oil_change.bus') }}</th>
                        <th>{{ __('messages.oil_change.last_change_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.interval_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.current_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.next_due_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.next_scheduled_km') }}</th>
                        <th class="text-end">{{ __('messages.oil_change.remaining_km') }}</th>
                        <th class="text-center">{{ __('messages.oil_change.column_status') }}</th>
                        {{-- ✅ Latest daily bus status (bus_daily_statuses) --}}
                        <th class="text-center">{{ __('messages.oil_change.daily_status') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $status = $row['statuses'][$activeType->value];
                            $bus = $row['bus'];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('oil-changes.show', $bus) }}" class="text-decoration-none">
                                    <strong>{{ $bus->dqn }}</strong>
                                </a>
                                @if($bus->route_number)
                                    <br>
                                    <small class="text-muted">
                                        {{ __('messages.daily_km.route_label', ['route' => $bus->route_number]) }}
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
                            <td class="text-end">
                                @if($status->lastChange)
                                    <strong>{{ number_format($status->lastChange->interval_km, 0, '', '.') }}</strong> km
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong>{{ number_format($status->currentKm, 0, '', '.') }}</strong> km
                            </td>
                            <td class="text-end">
                                @if($status->nextDueKm)
                                    {{ number_format($status->nextDueKm, 0, '', '.') }} km
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($status->nextCatalogKm)
                                    <strong style="color: #2563eb; font-size: 15px;">
                                        {{ number_format($status->nextCatalogKm, 0, '', '.') }}
                                    </strong> km
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($status->remainingKm === null)
                                    <span class="text-muted">—</span>
                                @elseif($status->isOverdue())
                                    <span class="text-danger fw-bold">
                                        −{{ number_format($status->overdueByKm(), 0, '', '.') }} km
                                    </span>
                                @else
                                    <strong>{{ number_format($status->remainingKm, 0, '', '.') }}</strong> km
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $status->bootstrapColor() }}">
                                    {{ $status->statusLabel() }}
                                </span>
                            </td>

                            {{-- ✅ Latest daily bus status --}}
                            <td class="text-center">
                                @php $daily = $bus->latestDailyStatus; @endphp
                                @if($daily)
                                    <span class="badge bg-secondary text-white"
                                          style="font-size: 11px; padding: 5px 10px; font-weight: 600; white-space: normal; max-width: 180px; display: inline-block; line-height: 1.3;">
                                        {{ $daily->status }}
                                    </span>
                                    <br>
                                    <small class="text-muted" style="font-size: 10px;">
                                        {{ $daily->date?->format('d.m.Y') ?? '' }}
                                    </small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
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
                                    <a href="{{ route('oil-changes.create', ['bus_id' => $bus->id, 'type' => $activeType->value]) }}"
                                       class="btn btn-sm btn-outline-success"
                                       title="{{ __('messages.oil_change.add_change') }}">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: .3;"></i>
                                @if($hasActiveFilters)
                                    {{ __('messages.oil_change.no_results') }}
                                    <br>
                                    <a href="{{ route('oil-changes.index', ['type' => $activeType->value]) }}"
                                       class="btn btn-sm btn-secondary mt-3">
                                        <i class="fas fa-xmark"></i> {{ __('messages.common.reset') }}
                                    </a>
                                @else
                                    {{ __('messages.reports.content.no_data') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────────── --}}
{{-- Total count — read by the parent page's JS after every AJAX   --}}
{{-- swap to keep the header counter in sync.                      --}}
{{--                                                                --}}
{{-- Without this element, index.blade.php's `syncBulkDeleteAllButton()` --}}
{{-- silently bails out (it does `querySelector('.total-count')` and    --}}
{{-- gets null), so the header count and the "delete all" button       --}}
{{-- number never update after a filter change.                        --}}
{{-- ─────────────────────────────────────────────────────────────── --}}
<span class="total-count d-none" data-count="{{ $rows->count() }}"></span>