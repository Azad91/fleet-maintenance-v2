<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kod</th>
                        <th>Ad</th>
                        <th>Miqdar</th>
                        <th>Ölçü Vahidi</th>
                        <th>Vahid Qiyməti</th>
                        <th>Cəmi Qiymət</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td><strong>{{ $item->code }}</strong></td> <!-- ✅ kod → code -->
                        <td>{{ $item->name }}</td> <!-- ✅ ad → name -->
                        <td>
                            {{ $item->quantity }} <!-- ✅ miqdar → quantity -->
                            @if($item->quantity <= 0)
                                <span class="badge bg-danger">⚠️ Bitib</span>
                            @elseif($item->quantity <= $item->minimum_quantity)
                                <span class="badge bg-warning">⚠️ Tükənir</span>
                            @endif
                        </td>
                        <td>{{ $item->unit ?? '-' }}</td> <!-- ✅ olcu_vahidi → unit -->
                        <td>{{ $item->price ? number_format($item->price, 2) . ' ₼' : '-' }}</td> <!-- ✅ qiymet → price -->
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
                                @if(Auth::user()->hasGarageRole(['admin', 'warehouse']))
                                    <a href="{{ route('warehouses.edit', $item) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('warehouses.destroy', $item) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Əminsən?')">
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
                                "<strong>{{ $search }}</strong>" üzrə heç nə tapılmadı
                            @else
                                Hələ anbarda məhsul yoxdur. <a href="{{ route('warehouses.create') }}">Yenisini əlavə et!</a>
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
