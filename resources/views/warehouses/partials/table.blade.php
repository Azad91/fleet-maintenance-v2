@php
    use App\Enums\RoleEnum;

    $currentUser = auth()->user();

    $canDeleteWarehouse = $currentUser?->isSuperAdmin()
        || $currentUser?->hasGarageRole(array_merge([RoleEnum::ADMIN->value], [RoleEnum::WAREHOUSE_MANAGER->value]));
@endphp

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('messages.warehouse.code') }}</th>
                        <th>{{ __('messages.warehouse.name') }}</th>
                        <th>{{ __('messages.warehouse.quantity') }}</th>
                        <th>{{ __('messages.warehouse.unit') }}</th>
                        <th>{{ __('messages.warehouse.price') }}</th>
                        <th>{{ __('messages.warehouse.total_price') }}</th>
                        <th>{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td><strong>{{ $item->code }}</strong></td>
                        <td>{{ $item->name }}</td>
                        <td>
                            {{ $item->quantity }}
                            @if($item->quantity <= 0)
                                <span class="badge bg-danger">⚠️ {{ __('messages.warehouse.out_of_stock') }}</span>
                            @elseif($item->quantity <= $item->minimum_quantity)
                                <span class="badge bg-warning">⚠️ {{ __('messages.warehouse.low_stock') }}</span>
                            @endif
                        </td>
                        <td>{{ $item->unit ?? '-' }}</td>
                        <td>{{ $item->price ? number_format($item->price, 2) . ' ₼' : '-' }}</td>
                        <td>
                            @if($item->price)
                                <strong>{{ number_format($item->quantity * $item->price, 2) }} ₼</strong>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('warehouses.show', $item) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $item)
                                    <a href="{{ route('warehouses.edit', $item) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @if($canDeleteWarehouse)
                                    <form action="{{ route('warehouses.destroy', $item) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('messages.common.confirm') }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-box-seam" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            @if(isset($search) && $search)
                                {{ __('messages.warehouse.no_results', ['search' => $search]) }}
                            @else
                                {{ __('messages.warehouse.no_items') }}
                                <a href="{{ route('warehouses.create') }}">{{ __('messages.warehouse.new') }}</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warehouses->hasPages())
            <div class="pagination-wrapper">
                {{ $warehouses->links() }}
            </div>
        @endif

        <span class="total-count d-none" data-count="{{ $warehouses->count() }}"></span>
    </div>
</div>
