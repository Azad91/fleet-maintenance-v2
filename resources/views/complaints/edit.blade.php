@extends('layouts.app')

@section('title', __('messages.complaints.edit_title'))

@section('content')
<div class="complaint-create-page">
    <div class="complaint-create-page__heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.complaints.eyebrow') }}</span>
            <h1>{{ __('messages.complaints.edit_title') }}</h1>
            <p>
                #{{ $complaint->id }}
                · {{ $complaint->bus?->dqn ?? '—' }}
                @if($complaint->created_at)
                    · {{ $complaint->created_at->format('d.m.Y') }}
                @endif
            </p>
        </div>
        <a href="{{ route('complaints.index') }}" class="fleet-button fleet-button--secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.complaints.back_to_cards') }}
        </a>
    </div>

    <div class="card complaint-create-card">
        <div class="card-header">
            <h4><i class="fas fa-screwdriver-wrench"></i> {{ __('messages.complaints.card_info') }}</h4>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="complaintForm"
                  action="{{ route('complaints.update', $complaint->id) }}"
                  method="POST">
                @csrf
                @method('PUT')
                @include('complaints.partials.form')
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('complaints.partials.form-scripts')
@endsection