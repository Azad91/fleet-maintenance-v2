<?php

return [
    'complaint_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'complaint_type' => [
        'accident' => 'Accident',
        'breakdown' => 'Breakdown',
        'maintenance' => 'Maintenance',
    ],

    'location' => [
        'road' => 'Road',
        'garage' => 'Garage',
    ],

    'transfer_status' => [
        'draft' => 'Draft',
        'dispatched' => 'Dispatched',
        'received' => 'Received',
        'disputed' => 'Disputed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'resolved' => 'Resolved',
    ],

    'transfer_type' => [
        'garage_to_garage' => 'Garage to Garage',
        'to_service_vehicle' => 'To Service Vehicle',
        'return_to_quarantine' => 'Return / Quarantine',
    ],

    'oil_type' => [
        'motor' => 'Motor oil',
        'gearbox' => 'Gearbox oil',
        'axle' => 'Axle oil',
    ],
];
