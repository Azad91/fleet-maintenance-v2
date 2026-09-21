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

        {{-- Search filter — helpful on large platforms with many
             companies. When empty, all accessible garages are listed. --}}
        <form method="GET" action="{{ route('garage.selection') }}" class="mb-3" autocomplete="off">
            <label for="garage_search" class="form-label">{{ __('messages.common.search') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text"
                       id="garage_search"
                       name="q"
                       class="form-control"
                       value="{{ $search ?? '' }}"
                       placeholder="{{ __('messages.garage.search_placeholder') }}">
                <button type="submit" class="btn btn-primary">
                    {{ __('messages.common.search') }}
                </button>
                @if(! empty($search))
                    <a href="{{ route('garage.selection') }}" class="btn btn-secondary">
                        <i class="fas fa-xmark"></i>
                    </a>
                @endif
            </div>
        </form>

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

            @if($companies->isEmpty() && ! empty($search))
                <div class="fleet-guest__error">
                    {{ __('messages.garage.no_search_results', ['search' => $search]) }}
                </div>
            @endif

            <button type="submit" class="garage-selection__submit">
                <i class="fas fa-arrow-right"></i> {{ __('messages.garage.submit') }}
            </button>
        </form>
    </div>
</section>
@endsection
