<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SuperAdminAuthLoggingTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. SUCCESSFUL LOGIN
    // ==================================================================

    public function test_super_admin_login_emits_warning_log(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Log::spy();

        $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'password',
        ]);

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message, $context = []) {
                return $message === 'SuperAdmin logged in'
                    && isset($context['email'])
                    && $context['email'] === 'sa@test.com';
            })
            ->once();
    }

    public function test_regular_user_login_does_not_emit_super_admin_warning(): void
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        Log::spy();

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'password',
        ]);

        Log::shouldNotHaveReceived('warning');
    }

    // ==================================================================
    // 2. FAILED LOGIN ATTEMPTS
    // ==================================================================

    public function test_failed_super_admin_login_emits_warning_log(): void
    {
        User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('correct'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Log::spy();

        $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'wrong-password',
        ]);

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message, $context = []) {
                return $message === 'Failed SuperAdmin login attempt'
                    && isset($context['email'])
                    && $context['email'] === 'sa@test.com';
            })
            ->once();
    }

    public function test_failed_login_for_unknown_email_does_not_emit_super_admin_warning(): void
    {
        Log::spy();

        $this->post('/login', [
            'email' => 'nobody@test.com',
            'password' => 'anything',
        ]);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_failed_login_for_regular_user_does_not_emit_super_admin_warning(): void
    {
        User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('correct'),
            'role' => 'user',
            'is_active' => true,
        ]);

        Log::spy();

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'wrong',
        ]);

        Log::shouldNotHaveReceived('warning');
    }

    // ==================================================================
    // 3. CONTEXT IS INCLUDED
    // ==================================================================

    public function test_warning_log_includes_ip_and_request_id(): void
    {
        User::factory()->create([
            'email' => 'sa@test.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Log::spy();

        $this->post('/login', [
            'email' => 'sa@test.com',
            'password' => 'password',
        ]);

        Log::shouldHaveReceived('warning')
            ->withArgs(function ($message, $context = []) {
                return $message === 'SuperAdmin logged in'
                    && array_key_exists('ip', $context)
                    && array_key_exists('user_agent', $context)
                    && array_key_exists('request_id', $context);
            })
            ->once();
    }
}
