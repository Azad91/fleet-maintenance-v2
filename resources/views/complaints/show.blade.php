@extends('layouts.app')

@section('title', 'Şikayət Məlumatları')

@section('content')
<div class="card complaint-show-card">
    <div class="card-header complaint-show-card__header d-flex justify-content-between align-items-center">
        <h4 class="mb-0 complaint-show-card__title">📋 Şikayət Məlumatları</h4>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('complaints.pdf', $complaint) }}" target="_blank" rel="noopener" class="btn btn-sm complaint-show-card__pdf-btn">
                <i class="bi bi-file-earmark-pdf"></i> PDF / Çap et
            </a>
            <span class="badge-status {{ str_replace(' ', '-', $complaint->status) }}">{{ $complaint->status }}</span>
        </div>
    </div>
    <div class="card-body complaint-show-card__body">
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-bus-front me-2"></i>Avtobus Məlumatları</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>DQN</small>
                            <strong>{{ $complaint->bus->dqn ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>Xətt №</small>
                            <strong>{{ $complaint->bus->xett_no ?? '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>Yer</small>
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
                    <div class="col-md-3">
                        <div class="complaint-show-card__item">
                            <small>🧑‍✈️ Sürücü</small>
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

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-clipboard me-2"></i>Şikayətlər</h6>
                @php
                    $shikayetler = explode("\n", $complaint->shikayet ?? '');
                    $shikayetler = array_filter($shikayetler);
                @endphp

                @if(count($shikayetler) > 0)
                    @foreach($shikayetler as $index => $shikayet)
                        <div class="complaint-show-card__entry">
                            <span class="complaint-show-card__entry-number">{{ $index + 1 }}</span>
                            <strong>{{ trim($shikayet) }}</strong>
                        </div>
                    @endforeach
                @else
                    <div class="complaint-show-card__empty">
                        <p>Şikayət daxil edilməyib</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-clock me-2"></i>Tarix və Saat</h6>
                <div class="row g-3">
                    @if($complaint->yer == 'yol')
                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 Bildirilme</small>
                            <strong>
                                {{ $complaint->bildirilme_tarix ? \Carbon\Carbon::parse($complaint->bildirilme_tarix)->format('d.m.Y') : '-' }}
                                {{ $complaint->bildirilme_saat ? ' - ' . $complaint->bildirilme_saat : '' }}
                            </strong>
                        </div>
                    </div>
                    @endif

                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 İşə Başlama</small>
                            <strong>
                                {{ $complaint->is_baslama_tarix ? \Carbon\Carbon::parse($complaint->is_baslama_tarix)->format('d.m.Y') : '-' }}
                                {{ $complaint->is_baslama_saat ? ' - ' . $complaint->is_baslama_saat : '' }}
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="complaint-show-card__item">
                            <small>📅 İşin Bitməsi</small>
                            <strong>
                                {{ $complaint->is_bitme_tarix ? \Carbon\Carbon::parse($complaint->is_bitme_tarix)->format('d.m.Y') : '-' }}
                                {{ $complaint->is_bitme_saat ? ' - ' . $complaint->is_bitme_saat : '' }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="complaint-show-card__item complaint-show-card__item--wide">
                    <small>📊 KM (Yürüş)</small>
                    <strong>{{ $complaint->km ? number_format($complaint->km, 0, ',', '.') . ' km' : '-' }}</strong>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-tools me-2"></i>🔧 İstifadə Olunan Detallar</h6>

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
                        <div class="complaint-show-card__detail">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small>📌 Aid Olduğu Şikayət</small>
                                    <span class="complaint-show-card__pill">{{ $shikayetText }}</span>
                                </div>
                                <div class="col-md-2">
                                    <small>Detal Kodu</small>
                                    <strong>{{ $detal['kodu'] ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small>Detal Adı</small>
                                    <strong>{{ $detal['adi'] ?? '-' }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small>Depo Miqdarı</small>
                                    <strong>{{ $detal['depo_miqdari'] ?? '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small>İşlənən Miqdar</small>
                                    <strong class="complaint-show-card__danger">{{ $detal['islenen_miqdar'] ?? '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small>👤 İşi görən işçi</small>
                                    <strong>{{ $employeesById[$detal['employee_id'] ?? null]->full_name_with_position ?? '-' }}</strong>
                                </div>
                            </div>
                            @if(!empty($detal['qeyd']))
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="complaint-show-card__note">
                                        <small>📝 Görülən İşlər</small>
                                        <strong>{{ $detal['qeyd'] }}</strong>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="complaint-show-card__empty">
                        <p>Detal istifadə olunmayıb</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <h6 class="complaint-show-card__section-title"><i class="bi bi-info-circle me-2"></i>ℹ️ Əlavə Məlumatlar</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="complaint-show-card__item">
                            <small>Yaradılma</small>
                            <strong>{{ $complaint->created_at ? \Carbon\Carbon::parse($complaint->created_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="complaint-show-card__item">
                            <small>Son Yenilənmə</small>
                            <strong>{{ $complaint->updated_at ? \Carbon\Carbon::parse($complaint->updated_at)->format('d.m.Y H:i') : '-' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('complaints.index') }}" class="btn btn-secondary complaint-show-card__back-btn">
                <i class="bi bi-arrow-left me-1"></i> Geri
            </a>
        </div>
    </div>
</div>
@endsection
