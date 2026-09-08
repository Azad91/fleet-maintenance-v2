@extends('layouts.guest')

@section('title', 'Select Garage')

@section('content')
    <section class="garage-selection">
        <div class="garage-selection__intro">
            <span class="fleet-eyebrow">SELECT YOUR WORK ENVIRONMENT</span>
            <h1>Which garage will you work in?</h1>
            <p>Based on your selection, buses, cards, warehouse and employee data will be filtered accordingly.</p>
            <div class="garage-selection__features"><span><i class="fas fa-building"></i> Data separated by company</span><span><i class="fas fa-shield-halved"></i> Garages you have access to</span></div>
        </div>
        <div class="garage-selection__card">
            <span class="garage-selection__icon"><i class="fas fa-warehouse"></i></span><h2>Garage Selection</h2><p>Select a garage to continue working.</p>
            <form action="{{ route('garage.select') }}" method="POST">
                @csrf
                <label for="garage_id" class="form-label">Company and Garage</label>
                <select name="garage_id" id="garage_id" class="form-select form-select-lg" required autofocus>
                    <option value="">Select garage…</option>
                    @foreach($companies as $company)
                        <optgroup label="{{ $company->name }}">@foreach($company->garages as $garage)<option value="{{ $garage->id }}">{{ $garage->name }} · {{ $garage->code }}</option>@endforeach</optgroup>
                    @endforeach
                </select>
                @error('garage_id')<div class="fleet-guest__error">{{ $message }}</div>@enderror
                <button type="submit" class="garage-selection__submit"><i class="fas fa-arrow-right"></i> Enter Garage</button>
            </form>
        </div>
    </section>
@endsection