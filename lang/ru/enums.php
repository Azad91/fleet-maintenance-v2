<?php

return [
    'complaint_status' => [
        'pending' => 'В ожидании',
        'in_progress' => 'В работе',
        'completed' => 'Завершено',
        'cancelled' => 'Отменено',
    ],
    'complaint_type' => [
        'accident' => 'Авария',
        'breakdown' => 'Поломка',
        'maintenance' => 'Техобслуживание',
    ],
    'location' => [
        'road' => 'Дорога',
        'garage' => 'Гараж',
    ],
    'transfer_status' => [
        'draft' => 'Черновик',
        'dispatched' => 'Отправлено',
        'received' => 'Получено',
        'disputed' => 'Оспорено',
        'rejected' => 'Отклонено',
        'cancelled' => 'Отменено',
        'resolved' => 'Решено',
    ],

    'transfer_type' => [
        'garage_to_garage' => 'Из гаража в гараж',
        'to_service_vehicle' => 'На сервисный автомобиль',
        'return_to_quarantine' => 'Возврат / Карантин',
    ],
];
