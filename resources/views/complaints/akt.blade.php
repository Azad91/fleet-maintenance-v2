<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Work Card - Act</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; }
        .content { margin-top: 30px; }
        .row { display: flex; margin-bottom: 10px; }
        .label { font-weight: bold; width: 150px; }
        .value { flex: 1; }
        @page { margin: 24px 28px; }
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
        <div class="title">WORK CARD / ACT</div>
        <div>No: {{ $complaint->id }} | Date: {{ now()->format('d.m.Y') }}</div>
    </div>

    <div class="content">
        <!-- Bus Information -->
        <div class="row"><span class="label">Bus:</span><span class="value">{{ $complaint->bus->dqn ?? '-' }} ({{ $complaint->bus->route_number ?? '-' }})</span></div>
        <div class="row"><span class="label">Location:</span><span class="value">{{ $complaint->yer ?? '-' }}</span></div>
        <div class="row"><span class="label">Driver:</span><span class="value">{{ $complaint->driver_name ?? '-' }}</span></div>
        <div class="row"><span class="label">KM:</span><span class="value">{{ $complaint->km ?? '-' }}</span></div>

        <!-- Complaints -->
        <div class="row">
            <span class="label">Complaints:</span>
            <span class="value">
                @foreach($complaint->items as $item)
                    {{ $item->description }}@if(!$loop->last), @endif
                @endforeach
            </span>
        </div>
        <div class="row"><span class="label">Notes:</span><span class="value">{{ $complaint->notes ?? '-' }}</span></div>
        <div class="row"><span class="label">Type:</span><span class="value">{{ $complaint->complaint_type ?? '-' }}</span></div>
        <div class="row"><span class="label">Status:</span><span class="value">{{ $complaint->status ?? '-' }}</span></div>

        <!-- Used Parts -->
        @if($complaint->details && $complaint->details->count() > 0)
            <h3>🔧 Used Parts</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Employee</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($complaint->details as $detal)
                        <tr>
                            <td>{{ $detal->code ?? '-' }}</td>
                            <td>{{ $detal->name ?? '-' }}</td>
                            <td>{{ $detal->used_quantity ?? 0 }}</td>
                            <td>{{ $employeesById[$detal->employee_id ?? null]->full_name_with_position ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Work Done -->
        <div class="row"><span class="label">Work Done:</span><span class="value">{{ $complaint->work_done_by ?? '-' }}</span></div>
        <div class="row"><span class="label">Start:</span><span class="value">{{ $complaint->start_date ?? '-' }} {{ $complaint->start_time ?? '' }}</span></div>
        <div class="row"><span class="label">End:</span><span class="value">{{ $complaint->end_date ?? '-' }} {{ $complaint->end_time ?? '' }}</span></div>
    </div>

    <!-- Signatures -->
    <div class="signature">
        <div>Master / Executor</div>
        <div>Manager / Approval</div>
    </div>

    <div class="footer">This document was created on {{ now()->format('d.m.Y H:i') }}</div>
</body>
</html>