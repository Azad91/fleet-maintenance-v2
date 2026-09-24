@extends('layouts.app')

@section('title', __('messages.two_factor.recovery_title'))

@section('content')
<div class="fleet-dashboard">
    {{-- ─── Page Heading ─── --}}
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
            <h1>{{ __('messages.two_factor.recovery_title') }}</h1>
            <p>{{ __('messages.two_factor.recovery_warning') }}</p>
        </div>
    </section>

    {{-- ─── Warning ─── --}}
    <div class="fleet-alert fleet-alert--warning mb-4">
        <i class="fas fa-exclamation-triangle"></i>
        {{ __('messages.two_factor.recovery_warning') }}
    </div>

    {{-- ─── Codes Panel ─── --}}
    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.two_factor.recovery_title') }}</span>
                <h2>
                    <i class="fas fa-key"></i>
                    {{ __('messages.two_factor.recovery_codes') }}
                </h2>
            </div>
        </header>

        <div class="p-4">
            <div class="row g-3">
                @foreach($codes as $code)
                    <div class="col-md-6">
                        <div class="recovery-code-block">
                            <code>{{ $code }}</code>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('super-admin.settings.index') }}" class="fleet-button fleet-button--primary">
                    <i class="fas fa-check"></i> {{ __('messages.two_factor.saved_codes') }}
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .recovery-code-block {
        padding: 14px 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        text-align: center;
        transition: all 0.15s;
    }
    .recovery-code-block:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }
    .recovery-code-block code {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 2px;
        color: #1e293b;
    }

    html[data-fleet-theme="dark"] .recovery-code-block {
        background: #1d2a3d;
        border-color: #3a4c64;
    }
    html[data-fleet-theme="dark"] .recovery-code-block:hover {
        background: #1e3a8a;
        border-color: #4f8cff;
    }
    html[data-fleet-theme="dark"] .recovery-code-block code {
        color: #e2eaf5;
    }
</style>
@endpush