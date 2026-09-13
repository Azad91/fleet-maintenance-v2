<?php

namespace App\Models\Traits;

use App\Exceptions\MissingGarageContextException;
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
     * No garage context found — block the operation.
     *
     * Behavior by environment:
     *   - Real console (migration, seeder, tinker): continue silently.
     *     Operators running those commands explicitly control the DB.
     *   - Everywhere else — including unit tests and web requests —
     *     throw MissingGarageContextException. The exception is caught
     *     by bootstrap/app.php and either returns JSON or redirects
     *     the user to garage selection.
     *
     * Reasoning: silently writing `garage_id = NULL` creates ghost
     * rows invisible to all garages and all reports. It is always
     * preferable to fail loudly.
     */
    protected static function handleMissingGarageContext($model): void
    {
        // Real console (NOT tests): continue silently.
        //
        // Note: PHPUnit tests also run in console, so we must
        // additionally check runningUnitTests() to distinguish the
        // two. Without it, every test would skip this guard.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        // Log before throwing — helps diagnosis even when the exception
        // is caught and converted into a user-facing redirect.
        Log::error('Garage context missing — blocking write', [
            'model'      => get_class($model),
            'attributes' => collect($model->getAttributes())
                ->except(['password', 'remember_token'])
                ->toArray(),
            'request_id' => Context::get('request_id'),
            'user_id'    => auth()->id(),
            'url'        => request()?->fullUrl(),
        ]);

        throw new MissingGarageContextException(get_class($model));
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
