<?php

return [
    'complaint_status' => [
        'pending' => 'Gözləmədə',
        'in_progress' => 'İşdə',
        'completed' => 'Həll olundu',
        'cancelled' => 'Ləğv edildi',
    ],

    'complaint_type' => [
        'accident' => 'Qəzalı',
        'breakdown' => 'Nasazlıq',
        'maintenance' => 'Texniki xidmət',
    ],

    'location' => [
        'road' => 'Yol',
        'garage' => 'Qaraj',
    ],

    'transfer_status' => [
        'draft' => 'Qaralama',
        'dispatched' => 'Göndərildi',
        'received' => 'Qəbul edildi',
        'disputed' => 'Fərq var',
        'rejected' => 'Rədd edildi',
        'cancelled' => 'Ləğv edildi',
        'resolved' => 'Həll olundu',
    ],

    'transfer_type' => [
        'garage_to_garage' => 'Qarajdan Qaraja',
        'to_service_vehicle' => 'Servis Maşınına',
        'return_to_quarantine' => 'İadə / Karantin',
    ],

    'oil_type' => [
        'motor' => 'Motor yağı',
        'gearbox' => 'Korobka yağı',
        'axle' => 'Most yağı',
    ],
];
