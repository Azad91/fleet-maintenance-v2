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

            // Console-da context yoxdursa filter tətbiq etmə
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
            // 1. Artıq garage_id təyin olunubsa, toxunma (manual override)
            if ($model->garage_id !== null) {
                return;
            }

            // 2. GarageContext-dən (ən etibarlı yol)
            if (GarageContext::has()) {
                $model->garage_id  = GarageContext::getGarageId();
                $model->company_id = GarageContext::getCompanyId();
                return;
            }

            // 3. Session-dan (web request)
            $sessionGarageId = self::resolveGarageFromSession();
            if ($sessionGarageId) {
                $model->garage_id  = $sessionGarageId;
                $model->company_id = session('current_company_id');
                return;
            }

            // 4. Auth user-dən (fallback)
            $userGarageId = self::resolveGarageFromAuth();
            if ($userGarageId) {
                $model->garage_id  = $userGarageId;
                $model->company_id = auth()->user()?->current_company_id;
                return;
            }

            // 5. Heç biri yoxdursa — kontekst yox
            self::handleMissingGarageContext($model);
        });
    }

    /**
     * Session-dan garage_id oxuyur.
     *
     * `session()` helper istifadə edirik — bu, Laravel-in
     * cari request üçün düzgün session store-u qaytarır.
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
     * Auth user-dən garage_id oxuyur.
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
     * Kontekst tamamilə yoxdursa nə et.
     *
     * - Real console (migration/seeder/tinker): sükutla davam et
     * - Test mühiti: log/exception davranışı yoxlanılsın deyə davam et
     * - Debug rejimi: exception at — developer dərhal görsün
     * - Production rejimi: log yaz, davam et (server stability)
     */
    protected static function handleMissingGarageContext($model): void
    {
        // Real console-da (test OLMAYAN) sükutla davam et
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $message = sprintf(
            'Garage context is not set. Model: %s. '
            . 'Set it via GarageContext::set(), session("current_garage_id") '
            . 'or auth()->user()->current_garage_id.',
            get_class($model)
        );

        // Debug rejimində exception at — developer dərhal görsün
        if (config('app.debug')) {
            throw new \RuntimeException($message);
        }

        // Production-da log yaz, davam et
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
