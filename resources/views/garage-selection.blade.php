@extends('layouts.app')

@section('title', 'Qaraj Seçimi')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4><i class="fas fa-warehouse"></i> Qaraj Seçimi</h4>
                    <p class="mb-0 text-white-50">İşləmək istədiyiniz qarajı seçin</p>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('garage.select') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="garage_id" class="form-label fw-bold">🏠 Qaraj</label>
                            <select name="garage_id" id="garage_id" class="form-select form-select-lg" required>
                                <option value="">-- Qaraj seçin --</option>
                                @foreach($companies as $company)
                                    <optgroup label="{{ $company->name }}">
                                        @foreach($company->garages as $garage)
                                            <option value="{{ $garage->id }}">
                                                {{ $garage->name }} ({{ $garage->code }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <small class="text-muted">Hansı qarajda işləmək istəyirsiniz?</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Daxil Ol
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
