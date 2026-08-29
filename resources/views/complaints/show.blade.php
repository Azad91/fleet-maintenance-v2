@extends('layouts.app')

@section('title', 'Şikayət Məlumatları')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">📋 Şikayət Məlumatları</h4>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('complaints.pdf', $complaint) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-pdf"></i> PDF / Çap et
            </a>
            <span class="badge-status {{ str_replace(' ', '-', $complaint->status) }}">{{ $complaint->status }}</span>
        </div>
    </div>
    <div class="card-body">
        <!-- Avtobus Məlumatları -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-bus-front me-2"></i>Avtobus Məlumatları</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">DQN</small>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">Xətt №</small>
                            <strong>{{ $complaint->bus->xett_no ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">Yer</small>
                            <strong>
                                @if($complaint->yer == 'yol')
                                    🛣️ Yol
                                @elseif($complaint->yer == 'qaraj')
                                    🏠 Qaraj
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
                    </div>
                    <!-- Sürücü -->
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">🧑‍✈️ Sürücü</small>
                            <strong>
                                @if($complaint->driver)
                                    {{ $complaint->driver->full_name }} ({{ $complaint->driver->kodu }})
                                @else
                                    {{ $complaint->surucu_adi ?? '-' }}
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Şikayətlər -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="fw-bold text-warning mb-3"><i class="bi bi-clipboard me-2"></i>Şikayətlər</h6>
                @php
                    $shikayetler = explode("\n", $complaint->shikayet ?? '');
                    $shikayetler = array_filter($shikayetler);
                @endphp

                @if(count($shikayetler) > 0)
                    @foreach($shikayetler as $index => $shikayet)
                        <div class="p-2 mb-2 bg-light rounded d-flex align-items-start">
                            <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                            <strong>{{ trim($shikayet) }}</strong>
                        </div>
                    @endforeach
                @else
                    <div class="p-3 bg-light rounded">
                        <p class="mb-0 text-muted">Şikayət daxil edilməyib</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tarix və Saat -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="fw-bold text-info mb-3"><i class="bi bi-clock me-2"></i>Tarix və Saat</h6>
                <div class="row">
                    @if($complaint->yer == 'yol')
                    <div class="col-md-4">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">📅 Bildirilme</small>
                            <strong>
                                {{ $complaint->bildirilme_tarix ? \Carbon\Carbon::parse($complaint->bildirilme_tarix)->format('d.m.Y') : '-' }}
                                {{ $complaint->bildirilme_saat ? ' - ' . $complaint->bildirilme_saat : '' }}
                            </strong>
                        </div>
                    </div>
                    @endif

                    <div class="col-md-4">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">📅 İşə Başlama</small>
                            <strong>
                                {{ $complaint->is_baslama_tarix ? \Carbon\Carbon::parse($complaint->is_baslama_tarix)->format('d.m.Y') : '-' }}
                                {{ $complaint->is_baslama_saat ? ' - ' . $complaint->is_baslama_saat : '' }}
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">📅 İşin Bitməsi</small>
                            <strong>
                                {{ $complaint->is_bitme_tarix ? \Carbon\Carbon::parse($complaint->is_bitme_tarix)->format('d.m.Y') : '-' }}
                                {{ $complaint->is_bitme_saat ? ' - ' . $complaint->is_bitme_saat : '' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KM -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-2 bg-light rounded">
                    <small class="text-muted d-block">📊 KM (Yürüş)</small>
                    <strong>{{ $complaint->km ? number_format($complaint->km, 0, ',', '.') . ' km' : '-' }}</strong>
                </div>
            </div>
        </div>

    <!-- Detallar -->
    <div class="row mb-4">
        <div class="col-12">
            <h6 class="fw-bold text-success mb-3"><i class="bi bi-tools me-2"></i>🔧 İstifadə Olunan Detallar</h6>

            @php
                $detallar = is_array($complaint->detallar) ? $complaint->detallar : json_decode($complaint->detallar, true);
                $shikayetler = explode("\n", $complaint->shikayet ?? '');
                $shikayetler = array_filter($shikayetler);
            @endphp

            @if($detallar && count($detallar) > 0)
                @foreach($detallar as $detal)
                    @php
                        $shikayetIndex = $detal['shikayet_index'] ?? 0;
                        $shikayetText = isset($shikayetler[$shikayetIndex]) ? trim($shikayetler[$shikayetIndex]) : "Şikayət " . ($shikayetIndex + 1);
                    @endphp
                    <div class="detail-card border rounded p-3 mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted d-block">📌 Aid Olduğu Şikayət</small>
                                <span class="badge bg-primary">{{ $shikayetText }}</span>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted d-block">Detal Kodu</small>
                                <strong>{{ $detal['kodu'] ?? '-' }}</strong>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted d-block">Detal Adı</small>
                                <strong>{{ $detal['adi'] ?? '-' }}</strong>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted d-block">Depo Miqdarı</small>
                                <strong>{{ $detal['depo_miqdari'] ?? '-' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">İşlənən Miqdar</small>
                                <strong class="text-danger">{{ $detal['islenen_miqdar'] ?? '-' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">👤 İşi görən işçi</small>
                                <strong>{{ $employeesById[$detal['employee_id'] ?? null]->full_name_with_position ?? '-' }}</strong>
                            </div>
                        </div>
                        @if(!empty($detal['qeyd']))
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="p-2 bg-light rounded">
                                    <small class="text-muted d-block">📝 Görülən İşlər</small>
                                    <strong>{{ $detal['qeyd'] }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="p-3 bg-light rounded">
                    <p class="mb-0 text-muted">Detal istifadə olunmayıb</p>
                </div>
            @endif
        </div>
    </div>

        <!-- Əlavə məlumatlar -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-info-circle me-2"></i>ℹ️ Əlavə Məlumatlar</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">Yaradılma</small>
                            <strong>{{ $complaint->created_at ? \Carbon\Carbon::parse($complaint->created_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-light rounded">
                            <small class="text-muted d-block">Son Yenilənmə</small>
                            <strong>{{ $complaint->updated_at ? \Carbon\Carbon::parse($complaint->updated_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Düymələr -->
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Geri
            </a>
        </div>
    </div>
</div>
@endsection
