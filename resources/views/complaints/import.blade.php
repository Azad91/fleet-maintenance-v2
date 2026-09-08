@extends('layouts.app')

@section('title', 'Import Complaints from Excel')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>📂 Import Complaints from Excel</h4>
    </div>
    <div class="card-body">
        <form action="{{ route('complaints.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Excel Format:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>bus_dqn</strong> - Bus DQN <span class="text-danger">*</span> (required)</li>
                    <li><strong>yer</strong> - Location (road / garage)</li>
                    <li><strong>surucu_adi</strong> - Driver name</li>
                    <li><strong>shikayet</strong> - Complaint text</li>
                    <li><strong>sikayet_tipi</strong> - Complaint type (accident / breakdown / maintenance)</li>
                    <li><strong>bildirilme_tarix</strong> - Reported date (Y-m-d)</li>
                    <li><strong>bildirilme_saat</strong> - Reported time (H:i)</li>
                    <li><strong>is_baslama_tarix</strong> - Start date (Y-m-d)</li>
                    <li><strong>is_baslama_saat</strong> - Start time (H:i)</li>
                    <li><strong>is_bitme_tarix</strong> - End date (Y-m-d)</li>
                    <li><strong>is_bitme_saat</strong> - End time (H:i)</li>
                    <li><strong>status</strong> - Status (pending / in_progress / completed)</li>
                    <li><strong>detal_kodu</strong> - Part code</li>
                    <li><strong>detal_adi</strong> - Part name</li>
                    <li><strong>islenen_miqdar</strong> - Used quantity</li>
                    <li><strong>km</strong> - Mileage</li>
                    <li><strong>qeyd</strong> - Notes</li>
                    <li><strong>kim_is_gorub</strong> - Who performed the work</li>
                </ul>
                <p class="mt-2 mb-0 text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>depo_miqdari</strong> - Automatically fetched from warehouse, no need to write in Excel.
                </p>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label fw-bold">Select Excel File (.xlsx, .xls, .csv)</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls,.csv" required>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> Import
                </button>
                <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection