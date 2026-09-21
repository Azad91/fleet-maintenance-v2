<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

/**
 * Centralized accessor for the "current garage" and "current company"
 * of the active request.
 *
 * Resolution order (first hit wins):
 *   1. Laravel Context      — set by middleware, most reliable
 *   2. Session              — set at login / garage selection
 *   3. Authenticated user   — persisted preference; needed for queue
 *                             jobs and console contexts
 *   4. null                 — no context available
 *
 * Two read APIs:
 *   - getGarageId() / getCompanyId(): raw Context only. Use when the
 *     caller is guaranteed to run behind the garage.selected middleware.
 *   - resolveGarageId() / resolveCompanyId(): full fallback chain. Use
 *     in services, models, and background jobs.
 */
class GarageContext
{
    // ==================== WRITE ====================

    public static function set(int $garageId, ?int $companyId = null): void
    {
        Context::add('current_garage_id', $garageId);
        Context::add('current_company_id', $companyId);
    }

    /**
     * Populate Context from a User's persisted current_garage_id.
     *
     * Returns true when the Context was set. The garage is verified
     * to exist and to be active; a user whose persisted garage has
     * since been soft-deleted or deactivated is treated as having no
     * context, so downstream queries fall back to the standard
     * "missing context" path instead of operating on a dead tenant.
     */
    public static function fromUser(User $user): bool
    {
        if (! $user->current_garage_id) {
            return false;
        }

        $garage = Garage::withoutGlobalScopes()
            ->whereKey($user->current_garage_id)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->first();

        if (! $garage) {
            return false;
        }

        self::set(
            (int) $garage->id,
            $garage->company_id ? (int) $garage->company_id : null,
        );

        return true;
    }

    public static function clear(): void
    {
        Context::forget('current_garage_id');
        Context::forget('current_company_id');
    }

    // ==================== READ (raw Context) ====================

    /**
     * Raw Context value — no fallback. Use only when the caller is
     * guaranteed to run behind `garage.selected`.
     */
    public static function getGarageId(): ?int
    {
        return Context::get('current_garage_id');
    }

    public static function getCompanyId(): ?int
    {
        return Context::get('current_company_id');
    }

    public static function has(): bool
    {
        return Context::has('current_garage_id');
    }

    // ==================== READ (resolved with fallback) ====================

    /**
     * Resolve the current garage id, walking the full fallback chain.
     */
    public static function resolveGarageId(): ?int
    {
        return self::getGarageId()
            ?? self::fromSession('current_garage_id')
            ?? self::fromAuth('current_garage_id');
    }

    /**
     * Resolve the current company id, walking the full fallback chain.
     */
    public static function resolveCompanyId(): ?int
    {
        return self::getCompanyId()
            ?? self::fromSession('current_company_id')
            ?? self::fromAuth('current_company_id');
    }

    /**
     * Resolve the full Garage model, if the resolved id points to an
     * existing record.
     */
    public static function resolveGarage(): ?Garage
    {
        $id = self::resolveGarageId();

        return $id ? Garage::withoutGlobalScopes()->find($id) : null;
    }

    // ==================== FALLBACK HELPERS ====================

    private static function fromSession(string $key): ?int
    {
        try {
            $value = session($key);

            return $value !== null ? (int) $value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function fromAuth(string $key): ?int
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return null;
            }

            $value = $user->{$key} ?? null;

            return $value !== null ? (int) $value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
