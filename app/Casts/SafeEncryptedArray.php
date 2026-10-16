<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * @implements CastsAttributes<?array<array-key, mixed>, ?array<array-key, mixed>>
 */
class SafeEncryptedArray implements CastsAttributes
{
    /**
     * Cast the given value from encrypted JSON storage.
     *
     * If decryption fails (e.g., due to an APP_KEY rotation or corrupted MAC),
     * this returns null instead of throwing an unhandled DecryptException 500 error.
     *
     * @param  array<string, mixed>  $attributes
     * @return ?array<array-key, mixed>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || ! is_string($value)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            $decoded = json_decode($decrypted, true);

            return is_array($decoded) ? $decoded : null;
        } catch (DecryptException $e) {
            Log::warning("SafeEncryptedArray: failed to decrypt '{$key}' on ".get_class($model)." #{$model->getKey()} (invalid MAC / rotated APP_KEY).");

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

        return Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR));
    }
}
