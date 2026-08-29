@extends('layouts.guest')

@section('title', 'Qaraj seçimi')

@section('content')
    <section class="garage-selection">
        <div class="garage-selection__intro">
            <span class="fleet-eyebrow">İŞ MÜHİTİNİ SEÇİN</span>
            <h1>Hansı qarajda işləyəcəksiniz?</h1>
            <p>Seçdiyiniz qaraja uyğun avtobuslar, kartlar, anbar və işçi məlumatları açılacaq.</p>
            <div class="garage-selection__features"><span><i class="fas fa-building"></i> Şirkətə görə ayrılmış məlumatlar</span><span><i class="fas fa-shield-halved"></i> İcazəniz olan qarajlar</span></div>
        </div>
        <div class="garage-selection__card">
            <span class="garage-selection__icon"><i class="fas fa-warehouse"></i></span><h2>Qaraj seçimi</h2><p>İşə davam etmək üçün bir qaraj seçin.</p>
            <form action="{{ route('garage.select') }}" method="POST">
                @csrf
                <label for="garage_id" class="form-label">Şirkət və qaraj</label>
                <select name="garage_id" id="garage_id" class="form-select form-select-lg" required autofocus>
                    <option value="">Qaraj seçin…</option>
                    @foreach($companies as $company)
                        <optgroup label="{{ $company->name }}">@foreach($company->garages as $garage)<option value="{{ $garage->id }}">{{ $garage->name }} · {{ $garage->code }}</option>@endforeach</optgroup>
                    @endforeach
                </select>
                @error('garage_id')<div class="fleet-guest__error">{{ $message }}</div>@enderror
                <button type="submit" class="garage-selection__submit"><i class="fas fa-arrow-right"></i> Qaraja daxil ol</button>
            </form>
        </div>
    </section>
@endsection
