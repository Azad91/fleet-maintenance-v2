<?php

namespace App\Models\Traits;

use App\Services\GarageContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

trait HasGarageScope
{
    protected static function bootHasGarageScope(): void
    {
        // ==================== GLOBAL SCOPE ====================
        static::addGlobalScope('garage', function (Builder $builder) {
            $garageId = GarageContext::getGarageId();

            // In console (without context), do not filter
            if (app()->runningInConsole() && ! $garageId) {
                return;
            }

            if ($garageId) {
                $builder->where(
                    $builder->getModel()->getTable() . '.garage_id',
                    $garageId
                );
            }
        });

        // ==================== CREATING EVENT ====================
        static::creating(function ($model) {
            // 1. If garage_id is already set, don't touch it (manual override)
            if ($model->garage_id !== null) {
                return;
            }

            // 2. From GarageContext (most reliable)
            if (GarageContext::has()) {
                $model->garage_id  = GarageContext::getGarageId();
                $model->company_id = GarageContext::getCompanyId();
                return;
            }

            // 3. From session (web request)
            $sessionGarageId = self::resolveGarageFromSession();
            if ($sessionGarageId) {
                $model->garage_id  = $sessionGarageId;
                $model->company_id = session('current_company_id');
                return;
            }

            // 4. From auth user (fallback)
            $userGarageId = self::resolveGarageFromAuth();
            if ($userGarageId) {
                $model->garage_id  = $userGarageId;
                $model->company_id = auth()->user()?->current_company_id;
                return;
            }

            // 5. Neither — context missing
            self::handleMissingGarageContext($model);
        });

        // ==================== COMPANY_ID CONSISTENCY GUARD ====================
        // company_id must always match the garage's company. If a caller
        // provided a mismatched company_id, silently correct it to prevent
        // data corruption.
        static::creating(function ($model) {
            if ($model->garage_id && $model->company_id) {
                $garageCompanyId = \App\Models\Garage::withoutGlobalScopes()
                    ->whereKey($model->garage_id)
                    ->value('company_id');

                if ($garageCompanyId && (int) $model->company_id !== (int) $garageCompanyId) {
                    $attempted = $model->company_id;
                    $model->company_id = $garageCompanyId;

                    Log::warning('HasGarageScope: corrected mismatched company_id', [
                        'model'     => get_class($model),
                        'garage_id' => $model->garage_id,
                        'attempted' => $attempted,
                        'corrected' => $garageCompanyId,
                    ]);
                }
            }
        });
    }

    /**
     * Read garage_id from session.
     */
    protected static function resolveGarageFromSession(): ?int
    {
        try {
            $garageId = session('current_garage_id');

            return $garageId ? (int) $garageId : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Read garage_id from auth user.
     */
    protected static function resolveGarageFromAuth(): ?int
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return null;
            }

            $garageId = $user->current_garage_id ?? null;

            return $garageId ? (int) $garageId : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * No garage context found — decide what to do.
     *
     * - Real console (migration/seeder/tinker): continue silently
     * - Test environment: continue so tests can control behavior
     * - Debug mode: throw exception — developer sees immediately
     * - Production: log warning, continue (server stability)
     */
    protected static function handleMissingGarageContext($model): void
    {
        // Real console (NOT tests): continue silently
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $message = sprintf(
            'Garage context is not set. Model: %s. '
            . 'Set it via GarageContext::set(), session("current_garage_id") '
            . 'or auth()->user()->current_garage_id.',
            get_class($model)
        );

        // In debug mode: throw exception — developer sees immediately
        if (config('app.debug')) {
            throw new \RuntimeException($message);
        }

        // Production: log warning, continue
        Log::warning($message, [
            'model'      => get_class($model),
            'attributes' => collect($model->getAttributes())
                ->except(['password', 'remember_token'])
                ->toArray(),
            'request_id' => Context::get('request_id'),
            'user_id'    => auth()->id(),
            'url'        => request()?->fullUrl(),
        ]);
    }

    // ==================== RELATIONS ====================

    public function garage()
    {
        return $this->belongsTo(\App\Models\Garage::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
