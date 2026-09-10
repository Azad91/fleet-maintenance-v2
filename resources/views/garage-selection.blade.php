@extends('layouts.guest')

@section('title', __('messages.garage.title'))

@section('content')
<section class="garage-selection">
    <div class="garage-selection__intro">
        <span class="fleet-eyebrow">{{ __('messages.garage.eyebrow') }}</span>
        <h1>{{ __('messages.garage.heading') }}</h1>
        <p>{{ __('messages.garage.subtitle') }}</p>
        <div class="garage-selection__features">
            <span><i class="fas fa-building"></i> {{ __('messages.garage.feature_company') }}</span>
            <span><i class="fas fa-shield-halved"></i> {{ __('messages.garage.feature_access') }}</span>
        </div>
    </div>
    <div class="garage-selection__card">
        <span class="garage-selection__icon"><i class="fas fa-warehouse"></i></span>
        <h2>{{ __('messages.garage.card_title') }}</h2>
        <p>{{ __('messages.garage.card_subtitle') }}</p>
        <form action="{{ route('garage.select') }}" method="POST">
            @csrf
            <label for="garage_id" class="form-label">{{ __('messages.garage.label') }}</label>
            <select name="garage_id" id="garage_id" class="form-select form-select-lg" required autofocus>
                <option value="">{{ __('messages.garage.placeholder') }}</option>
                @foreach($companies as $company)
                    <optgroup label="{{ $company->name }}">
                        @foreach($company->garages as $garage)
                            <option value="{{ $garage->id }}">{{ $garage->name }} · {{ $garage->code }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('garage_id')<div class="fleet-guest__error">{{ $message }}</div>@enderror
            <button type="submit" class="garage-selection__submit">
                <i class="fas fa-arrow-right"></i> {{ __('messages.garage.submit') }}
            </button>
        </form>
    </div>
</section>
@endsection