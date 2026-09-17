{{-- Read-only items table used on the show page --}}
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 60px;">#</th>
                <th>{{ __('messages.transfers.item_code') }}</th>
                <th>{{ __('messages.complaints.part_name') }}</th>
                <th class="text-end">{{ __('messages.transfers.declared_qty') }}</th>
                <th class="text-end">{{ __('messages.transfers.received_qty') }}</th>
                <th class="text-end">{{ __('messages.transfers.discrepancy') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfer->items as $index => $item)
                @php
                    $warehouse = $item->warehouse;
                    $diff = $item->difference;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><code>{{ $warehouse?->code ?? '—' }}</code></td>
                    <td>
                        <strong>{{ $warehouse?->name ?? '—' }}</strong>
                        @if($warehouse?->unit)
                            <small class="text-muted d-block">{{ $warehouse->unit }}</small>
                        @endif
                    </td>
                    <td class="text-end"><strong>{{ $item->declared_quantity }}</strong></td>
                    <td class="text-end">
                        @if($item->received_quantity !== null)
                            <strong>{{ $item->received_quantity }}</strong>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($diff === null)
                            <span class="text-muted">—</span>
                        @elseif($diff === 0)
                            <span class="text-success">✓</span>
                        @else
                            <span class="text-danger fw-bold">
                                {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        {{ __('messages.transfers.no_transfers') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="3" class="text-end">{{ __('messages.transfers.declared_total') }}</th>
                <th class="text-end">{{ $transfer->declared_total }}</th>
                <th class="text-end">
                    @if($transfer->received_total !== null)
                        {{ $transfer->received_total }}
                    @else
                        —
                    @endif
                </th>
                <th class="text-end">
                    @if($transfer->received_total !== null)
                        @php $totalDiff = $transfer->received_total - $transfer->declared_total; @endphp
                        @if($totalDiff === 0)
                            <span class="text-success">✓</span>
                        @else
                            <span class="text-danger fw-bold">
                                {{ $totalDiff > 0 ? '+' : '' }}{{ $totalDiff }}
                            </span>
                        @endif
                    @else
                        —
                    @endif
                </th>
            </tr>
        </tfoot>
    </table>
</div>
