<?php

return [
    'intervals' => [
        'motor' => [
            '12m' => 36000,
            '18m' => 30000,
        ],
        'gearbox' => [
            'SHELL' => 180000,
            'LUK'   => 120000,
        ],
        'axle' => [
            'default' => 180000,
        ],
    ],

    'bus_length_threshold' => 15,

    'thresholds' => [
        'critical_km' => 1000,
        'due_soon_km' => 5000,
    ],
];
