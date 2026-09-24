<?php

namespace App\Support\Auth;

/**
 * Timing-attack defense helpers for authentication.
 *
 * WHEN A LOGIN TARGETS A NONEXISTENT CREDENTIAL
 * ---------------------------------------------
 * A naive login controller returns immediately when the email or
 * employee code does not exist:
 *
 *     $user = User::where('email', $email)->first();
 *     if (! $user) {
 *         return back()->withErrors(['email' => 'Invalid credentials']);
 *     }
 *
 * That branch returns in microseconds — no bcrypt check, no DB
 * round-trip beyond the initial SELECT. A successful "user exists"
 * branch, by contrast, runs a bcrypt comparison that takes tens of
 * milliseconds. The difference is measurable over the network, and
 * an attacker can enumerate valid emails by timing the response:
 *
 *     for each candidate email:
 *         t0 = now()
 *         POST /login  {email, password: "anything"}
 *         t1 = now()
 *         if (t1 - t0) > 50ms: email exists
 *
 * HOW THIS HELPER DEFEATS THAT
 * ----------------------------
 * We always run a bcrypt comparison, even when the user is missing,
 * by falling back to this constant hash. The comparison always fails
 * (the dummy hash is not the hash of any user's password), but the
 * timing profile of the two branches becomes indistinguishable.
 *
 * THE VALUE BELOW
 * ---------------
 * It is the bcrypt hash of the literal string "password" — the same
 * value Laravel's UserFactory generates by default. It contains no
 * real secret and is safe to commit to source control.
 */
final class TimingAttackDefense
{
    /**
     * A valid bcrypt hash used as a comparison target when the
     * submitted credential does not match any user.
     *
     * NEVER replace this with a hash of a real password. Its only
     * purpose is to burn CPU cycles in a way that is timing-identical
     * to a genuine "wrong password" check.
     */
    public const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
}
