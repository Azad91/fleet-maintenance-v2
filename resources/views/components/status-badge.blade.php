@props(['status' => null])

@php
    // Map enum value → CSS class
    $class = match($status) {
        'pending'     => 'pending',
        'in_progress' => 'in-progress',
        'completed'   => 'completed',
        'cancelled'   => 'cancelled',
        'active'      => 'active',
        'inactive'    => 'inactive',
        default       => '',
    };

    // Translate if it's a known complaint_status enum.
    // Translation keys live in the `enums.*` namespace
    // (see the lang/ directory for source).
    $knownStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    $label = in_array($status, $knownStatuses, true)
        ? __('enums.complaint_status.' . $status)
        : $status;
@endphp

<span {{ $attributes->merge(['class' => 'badge-status ' . $class]) }}>
    {{ $label }}
</span>
