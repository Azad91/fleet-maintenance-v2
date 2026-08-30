@extends('layouts.app')

@section('title', 'Kartlar')

@section('content')
<div class="page-header">
    <h1>📋 Kartlar</h1>
    <p class="text-muted">Bütün şikayət və iş kartları</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('complaints.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Yeni Kart
        </a>
        <a href="{{ route('complaints.import') }}" class="btn btn-success">
            <i class="bi bi-upload"></i> Excel-dən Yüklə
        </a>
        <!-- 🔥 YENİ: Şikayət Növləri Butonu -->
        <a href="{{ route('complaint-types.index') }}" class="btn btn-outline-info">
            <i class="bi bi-tags"></i> Şikayət Növləri
        </a>
    </div>
    <span class="badge bg-primary rounded-pill">Cəmi: {{ $complaints->total() }} kart</span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Avtobus</th>
                        <th>Şikayət</th>
                        <th>Tip</th>
                        <th>Status</th>
                        <th>Tarix</th>
                        <th>Əməliyyatlar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                            <br>
                            <small class="text-muted">{{ $complaint->bus->xett_no ?? '-' }}</small>
                        </td>
                        <td>{{ Str::limit($complaint->shikayet, 30) }}</td>
                        <td>
                            @if($complaint->sikayet_tipi == 'qezali')
                                <span class="badge bg-danger">🚗 Qəzalı</span>
                            @elseif($complaint->sikayet_tipi == 'nasazliq')
                                <span class="badge bg-warning">⚠️ Nasazlıq</span>
                            @elseif($complaint->sikayet_tipi == 'texniki_xidmet')
                                <span class="badge bg-info">🔧 Texniki Xidmət</span>
                            @else
                                <span class="badge bg-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            @if($complaint->status == 'həll olundu')
                                <span class="badge bg-success">✅ Bağlandı</span>
                            @elseif($complaint->status == 'işdə')
                                <span class="badge bg-warning">🔨 İşdə</span>
                            @else
                                <span class="badge bg-secondary">⏳ Gözləmədə</span>
                            @endif
                        </td>
                        <td>{{ $complaint->created_at ? $complaint->created_at->format('d.m.Y') : '-' }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <!-- Bax -->
                                <a href="{{ route('complaints.show', $complaint) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <!-- Redaktə -->
                                @if($complaint->status != 'həll olundu')
                                <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif

                                <!-- Bağla -->
                                @if($complaint->status != 'həll olundu')
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#closeModal{{ $complaint->id }}">
                                    <i class="bi bi-check-circle"></i> Bağla
                                </button>
                                @endif

                                <!-- Sil -->
                                @if(Auth::user()->hasGarageRole('admin'))
                                <form action="{{ route('complaints.destroy', $complaint) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bu kartı silmək istədiyinizdən əminsiniz?')">
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
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-clipboard" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            Hələ heç bir kart yoxdur.
                            <br>
                            <a href="{{ route('complaints.create') }}" class="btn btn-primary btn-sm mt-2">Yeni Kart Aç</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="pagination-wrapper d-flex justify-content-center mt-4">
    {{ $complaints->withQueryString()->links() }}
</div>

<!-- ==================== BAĞLANMA MODALLARI ==================== -->
@foreach($complaints as $complaint)
@if($complaint->status != 'həll olundu')
<div class="modal fade" id="closeModal{{ $complaint->id }}" tabindex="-1" aria-labelledby="closeModalLabel{{ $complaint->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('complaints.close', $complaint) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="closeModalLabel{{ $complaint->id }}">
                        <i class="bi bi-lock"></i> Şikayəti Bağla
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
                </div>
                <div class="modal-body">
                    <!-- Avtobus məlumatı -->
                    <div class="alert alert-info">
                        <strong>🚌 Avtobus:</strong> {{ $complaint->bus->dqn ?? '-' }}
                        ({{ $complaint->bus->xett_no ?? '-' }})
                    </div>

                    <div class="mb-3">
                        <label for="is_bitme_tarix{{ $complaint->id }}" class="form-label fw-bold">📅 Bitmə Tarixi <span class="text-danger">*</span></label>
                        <input type="date" name="is_bitme_tarix" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="mb-3">
                        <label for="is_bitme_saat{{ $complaint->id }}" class="form-label fw-bold">🕐 Bitmə Saatı <span class="text-danger">*</span></label>
                        <input type="time" name="is_bitme_saat" class="form-control" required value="{{ date('H:i') }}">
                    </div>

                    <div class="mb-3">
                        <label for="gorulen_is{{ $complaint->id }}" class="form-label fw-bold">📝 Görülən İşlər <span class="text-danger">*</span></label>
                        <textarea name="gorulen_is" class="form-control" rows="3" placeholder="Görülən işləri ətraflı yazın..." required></textarea>
                        <small class="text-muted">Ən azı 5 simvol daxil edin</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv Et</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Bağla
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@endsection

@section('scripts')
<script>
    // ==================== BOOTSTRAP 5 MODALLARI ====================
    document.addEventListener('DOMContentLoaded', function() {
        // Bütün modalları işə sal
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: false
            });
        });

        // Modal açılanda fokuslanma
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('shown.bs.modal', function() {
                var input = this.querySelector('input, textarea');
                if (input) {
                    input.focus();
                }
            });
        });
    });
</script>
@endsection
