<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * @implements CastsAttributes<?string, ?string>
 */
class SafeEncrypted implements CastsAttributes
{
    /**
     * Cast the given value from encrypted storage.
     *
     * If decryption fails (e.g., due to an APP_KEY rotation or corrupted MAC),
     * this returns null instead of throwing an unhandled DecryptException 500 error.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || ! is_string($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::warning("SafeEncrypted: failed to decrypt '{$key}' on ".get_class($model)." #{$model->getKey()} (invalid MAC / rotated APP_KEY).");

            return null;
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
