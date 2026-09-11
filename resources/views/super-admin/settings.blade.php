@extends('layouts.app')

@section('title', __('messages.super_admin.settings.title'))

@section('content')
<div class="fleet-dashboard">
    <section class="fleet-page-heading">
        <div>
            <span class="fleet-eyebrow">{{ __('messages.super_admin.eyebrow') }}</span>
            <h1>{{ __('messages.super_admin.settings.title') }}</h1>
            <p>{{ __('messages.super_admin.settings.subtitle') }}</p>
        </div>
    </section>

    @if(session('success'))
        <div class="fleet-alert fleet-alert--success">
            <i class="fas fa-circle-check"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="fleet-alert fleet-alert--error">
            <i class="fas fa-circle-exclamation"></i>{{ session('error') }}
        </div>
    @endif

    {{-- APPLICATION --}}
    <section class="fleet-panel mb-4">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.super_admin.settings.application') }}</span>
                <h2>{{ __('messages.super_admin.settings.application') }}</h2>
            </div>
        </header>
        <div class="fleet-table-wrap">
            <table class="fleet-table">
                <tbody>
                    <tr>
                        <td style="width: 30%; color: #64748b;"><strong>{{ __('messages.super_admin.settings.app_name') }}</strong></td>
                        <td>{{ $system['app_name'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.app_url') }}</strong></td>
                        <td><code>{{ $system['app_url'] }}</code></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.environment') }}</strong></td>
                        <td>
                            <span class="fleet-status {{ $system['app_env'] === 'production' ? 'fleet-status--success' : 'fleet-status--warning' }}">
                                {{ strtoupper($system['app_env']) }}
                            </span>
                            @if($system['app_debug'])
                                <span class="fleet-status fleet-status--warning ms-2">DEBUG ON</span>
                            @else
                                <span class="fleet-status fleet-status--muted ms-2">DEBUG OFF</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.timezone') }}</strong></td>
                        <td>{{ $system['timezone'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.locale') }}</strong></td>
                        <td>
                            <strong>{{ $system['locale'] }}</strong>
                            <small class="text-muted">(fallback: {{ $system['fallback_locale'] }})</small>
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.supported_locales') }}</strong></td>
                        <td>
                            @foreach($system['supported_locales'] as $locale)
                                <span class="badge text-bg-secondary me-1">{{ $locale }}</span>
                            @endforeach
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- RUNTIME --}}
    <section class="fleet-panel mb-4">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.super_admin.settings.runtime') }}</span>
                <h2>{{ __('messages.super_admin.settings.runtime') }}</h2>
            </div>
        </header>
        <div class="fleet-table-wrap">
            <table class="fleet-table">
                <tbody>
                    <tr>
                        <td style="width: 30%; color: #64748b;"><strong>PHP</strong></td>
                        <td>{{ $system['php_version'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>Laravel</strong></td>
                        <td>{{ $system['laravel_version'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.db_driver') }}</strong></td>
                        <td>{{ $system['db_driver'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.cache_driver') }}</strong></td>
                        <td>{{ $system['cache_driver'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.session_driver') }}</strong></td>
                        <td>{{ $system['session_driver'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.queue_driver') }}</strong></td>
                        <td>{{ $system['queue_driver'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.filesystem') }}</strong></td>
                        <td>{{ $system['filesystem_disk'] }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><strong>{{ __('messages.super_admin.settings.log_channel') }}</strong></td>
                        <td>{{ $system['log_channel'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- MAINTENANCE --}}
    <section class="fleet-panel">
        <header class="fleet-panel__header">
            <div>
                <span class="fleet-eyebrow">{{ __('messages.super_admin.settings.maintenance') }}</span>
                <h2>{{ __('messages.super_admin.settings.maintenance') }}</h2>
            </div>
        </header>
        <div class="p-4">
            <p class="text-muted mb-3">
                <i class="fas fa-info-circle"></i> {{ __('messages.super_admin.settings.cache_hint') }}
            </p>
            <form method="POST" action="{{ route('super-admin.settings.clear-cache') }}"
                  onsubmit="return confirm('{{ __('messages.super_admin.settings.cache_confirm') }}')">
                @csrf
                <button type="submit" class="fleet-button fleet-button--primary">
                    <i class="fas fa-broom"></i> {{ __('messages.super_admin.settings.clear_cache') }}
                </button>
            </form>
        </div>
    </section>
</div>
@endsection
