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
            // If garage_id is already set, don't touch it (manual override).
            if ($model->garage_id !== null) {
                return;
            }

            // Resolve via the centralized chain:
            // Context → session → auth user → null.
            $garageId  = GarageContext::resolveGarageId();
            $companyId = GarageContext::resolveCompanyId();

            if ($garageId !== null) {
                $model->garage_id  = $garageId;
                $model->company_id = $companyId;

                return;
            }

            // No context available — defer the decision to the handler.
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
