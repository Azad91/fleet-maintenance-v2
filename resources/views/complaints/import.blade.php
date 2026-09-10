@extends('layouts.app')

@section('title', __('messages.complaints.import_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 {{ __('messages.complaints.import_title') }}</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('complaints.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>{{ __('messages.complaints.import_format_title') }}:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>bus_dqn</strong> – {{ __('messages.buses.col_dqn') }} <span class="text-danger">*</span></li>
                    <li><strong>yer</strong> – {{ __('messages.complaints.location') }} (road / garage)</li>
                    <li><strong>driver_name</strong> – {{ __('messages.complaints.driver_name') }}</li>
                    <li><strong>complaints</strong> – {{ __('messages.complaints.complaint') }}</li>
                    <li><strong>complaint_type</strong> – {{ __('messages.complaints.complaint_type') }} (accident / breakdown / maintenance)</li>
                    <li><strong>reported_date</strong> – {{ __('messages.complaints.reported_date') }} (Y-m-d)</li>
                    <li><strong>reported_time</strong> – {{ __('messages.complaints.reported_time') }} (H:i)</li>
                    <li><strong>start_date</strong> – {{ __('messages.complaints.start_date') }} (Y-m-d)</li>
                    <li><strong>start_time</strong> – {{ __('messages.complaints.start_time') }} (H:i)</li>
                    <li><strong>end_date</strong> – {{ __('messages.complaints.end_date') }} (Y-m-d)</li>
                    <li><strong>end_time</strong> – {{ __('messages.complaints.end_time') }} (H:i)</li>
                    <li><strong>status</strong> – {{ __('messages.common.status') }} (pending / in_progress / completed)</li>
                    <li><strong>part_code</strong> – {{ __('messages.complaints.part_code') }}</li>
                    <li><strong>part_name</strong> – {{ __('messages.complaints.part_name') }}</li>
                    <li><strong>used_quantity</strong> – {{ __('messages.complaints.used_qty') }}</li>
                    <li><strong>km</strong> – {{ __('messages.buses.km') }}</li>
                    <li><strong>notes</strong> – {{ __('messages.common.notes') }}</li>
                    <li><strong>work_done_by</strong> – {{ __('messages.complaints.employee') }}</li>
                </ul>
                <p class="mt-2 mb-0 text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>stock_quantity</strong> – {{ __('messages.buses.import_note_auto') }}
                </p>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">{{ __('messages.buses.import_select_file') }}</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> {{ __('messages.buses.import_button') }}
                </button>
                <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> {{ __('messages.common.back') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection