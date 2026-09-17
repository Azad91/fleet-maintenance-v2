<?php

return [
    'complaint_status' => [
        'pending' => 'Beklemede',
        'in_progress' => 'İşlemde',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal edildi',
    ],
    'complaint_type' => [
        'accident' => 'Kaza',
        'breakdown' => 'Arıza',
        'maintenance' => 'Bakım',
    ],
    'location' => [
        'road' => 'Yol',
        'garage' => 'Garaj',
    ],
    'transfer_status' => [
        'draft'      => 'Taslak',
        'dispatched' => 'Sevk Edildi',
        'received'   => 'Teslim Alındı',
        'disputed'   => 'İhtilaflı',
        'rejected'   => 'Reddedildi',
        'cancelled'  => 'İptal Edildi',
        'resolved'   => 'Çözüldü',
    ],

    'transfer_type' => [
        'garage_to_garage'    => 'Garajdan Garaja',
        'to_service_vehicle'  => 'Servis Aracına',
        'return_to_quarantine' => 'İade / Karantina',
    ],
];
