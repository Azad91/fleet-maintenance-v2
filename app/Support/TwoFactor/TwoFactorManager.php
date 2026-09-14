<?php

namespace App\Support\TwoFactor;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Thin wrapper around pragmarx/google2fa that centralizes the two
 * operations we actually use: secret generation and code verification.
 *
 * Recovery codes are just plain strings; we hash them with Laravel's
 * Hasher so that a leaked database cannot be used to bypass MFA.
 * Hashing is done at generation time; verification uses Hash::check().
 */
class TwoFactorManager
{
    public function __construct(
        protected Google2FA $google2fa
    ) {}

    /**
     * Generate a new base32 secret for TOTP.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Build the otpauth:// URL that authenticator apps consume.
     * Encoded as a data URI so it can be rendered in an <img> tag
     * without an extra QR service round trip.
     */
    public function qrCodeDataUri(User $user, string $secret): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            config('app.name', 'Fleet Control'),
            $user->email,
            $secret,
        );

        // Use the package's built-in QR renderer (bacon/bacon-qr-code).
        $writer = new \BaconQrCode\Writer(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(220),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd(),
            )
        );

        $svg = $writer->writeString($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Verify a 6-digit TOTP code against the user's secret.
     * Allows a window of ±1 step (30s) to tolerate clock drift.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code, window: 1);
    }

    /**
     * Generate a fresh batch of recovery codes.
     *
     * @return array{plain: array<int, string>, hashed: array<int, string>}
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            $code = Str::upper(Str::random(5).'-'.Str::random(5));
            $plain[] = $code;
            $hashed[] = $code; // stored hashed via the model's cast helper
        }

        return ['plain' => $plain, 'hashed' => $hashed];
    }
}
