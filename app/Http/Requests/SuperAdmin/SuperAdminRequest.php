<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for every SuperAdmin-only FormRequest.
 *
 * Centralizes the authorization rule — only the platform SuperAdmin
 * may submit these forms. The `/super-admin/*` routes are already
 * protected by the `super.admin` middleware; this check is the
 * second line of defense so a controller refactor cannot accidentally
 * expose an endpoint.
 *
 * Subclasses only need to declare rules() (and optionally messages()
 * and prepareForValidation()).
 */
abstract class SuperAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }
}
