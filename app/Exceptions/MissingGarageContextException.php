<?php

namespace App\Exceptions;

use App\Models\Model;
use Exception;

/**
 * Thrown when a garage-scoped model is being created without an
 * active garage context.
 *
 * This is a hard failure by design. Silently creating orphan rows
 * with `garage_id = NULL` corrupts the multi-tenant data model:
 * the rows become invisible to every garage, excluded from every
 * report, and impossible to reconcile without direct DB access.
 *
 * Recognized contexts (in order of precedence):
 *   - GarageContext::set()/has()      — normal web request flow
 *   - session('current_garage_id')    — post-login session
 *   - auth()->user()->current_garage_id — persisted user preference
 *
 * If none of those are present, the operation must be blocked and
 * the user redirected to garage selection.
 */
class MissingGarageContextException extends Exception
{
    public function __construct(
        public readonly string $modelClass,
        string $message = '',
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message ?: sprintf(
                'Garage context is not set for model [%s]. '
                .'Set it via GarageContext::set(), session("current_garage_id") '
                .'or auth()->user()->current_garage_id.',
                $modelClass
            ),
            $code,
            $previous
        );
    }
}
