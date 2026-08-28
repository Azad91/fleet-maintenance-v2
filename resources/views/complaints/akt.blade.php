<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>İş Kartı - Akt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; }
        .content { margin-top: 30px; }
        .row { display: flex; margin-bottom: 10px; }
        .label { font-weight: bold; width: 150px; }
        .value { flex: 1; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f5f5f5; }
        .signature { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature div { width: 45%; border-top: 1px solid #333; padding-top: 10px; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">İŞ KARTI / AKT</div>
        <div>№: {{ $complaint->id }} | Tarix: {{ now()->format('d.m.Y') }}</div>
    </div>

    <div class="content">
        <!-- Avtobus Məlumatları -->
        <div class="row"><span class="label">Avtobus:</span><span class="value">{{ $complaint->bus->dqn ?? '-' }} ({{ $complaint->bus->xett_no ?? '-' }})</span></div>
        <div class="row"><span class="label">Yer:</span><span class="value">{{ $complaint->yer ?? '-' }}</span></div>
        <div class="row"><span class="label">Sürücü:</span><span class="value">{{ $complaint->surucu_adi ?? '-' }}</span></div>
        <div class="row"><span class="label">KM:</span><span class="value">{{ $complaint->km ?? '-' }}</span></div>

        <!-- Şikayət Məlumatları -->
        <div class="row"><span class="label">Şikayət:</span><span class="value">{{ $complaint->shikayet ?? '-' }}</span></div>
        <div class="row"><span class="label">Tip:</span><span class="value">{{ $complaint->sikayet_tipi ?? '-' }}</span></div>
        <div class="row"><span class="label">Status:</span><span class="value">{{ $complaint->status ?? '-' }}</span></div>

        <!-- İstifadə Olunan Detallar -->
        @if($complaint->detallar && is_array($complaint->detallar) && count($complaint->detallar) > 0)
        <h3>🔧 İstifadə Olunan Detallar</h3>
        <table class="table">
            <thead><tr><th>Kod</th><th>Ad</th><th>Miqdar</th></tr></thead>
            <tbody>
                @foreach($complaint->detallar as $detal)
                <tr><td>{{ $detal['kodu'] ?? '-' }}</td><td>{{ $detal['adi'] ?? '-' }}</td><td>{{ $detal['islenen_miqdar'] ?? 0 }}</td></tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Görülən İşlər -->
        <div class="row"><span class="label">Görülən İş:</span><span class="value">{{ $complaint->kim_is_gorub ?? '-' }}</span></div>
        <div class="row"><span class="label">Başlama:</span><span class="value">{{ $complaint->is_baslama_tarix ?? '-' }} {{ $complaint->is_baslama_saat ?? '' }}</span></div>
        <div class="row"><span class="label">Bitmə:</span><span class="value">{{ $complaint->is_bitme_tarix ?? '-' }} {{ $complaint->is_bitme_saat ?? '' }}</span></div>
    </div>

    <!-- İmzalar -->
    <div class="signature">
        <div>Usta / İcraçı</div>
        <div>Rəhbər / Təsdiq</div>
    </div>

    <div class="footer">Bu sənəd {{ now()->format('d.m.Y H:i') }} tarixində yaradılmışdır.</div>
</body>
</html>
