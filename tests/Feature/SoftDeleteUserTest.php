<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_delete_is_soft(): void
    {
        $user = User::factory()->create(['email' => 'soft-delete@test.com']);

        $user->delete();

        // Not present in normal queries
        $this->assertNull(User::find($user->id));

        // Still in DB with deleted_at set
        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id)->deleted_at);
    }

    public function test_soft_deleted_user_can_be_restored(): void
    {
        $user = User::factory()->create(['email' => 'restore@test.com']);
        $originalId = $user->id;

        $user->delete();
        $this->assertNull(User::find($originalId));

        User::withTrashed()->find($originalId)->restore();
        $this->assertNotNull(User::find($originalId));
    }

    public function test_email_can_be_reused_after_soft_delete(): void
    {
        $user = User::factory()->create(['email' => 'reuse@test.com']);
        $user->delete();

        // Should succeed because old email is on a soft-deleted row
        $newUser = User::factory()->create(['email' => 'reuse@test.com']);

        $this->assertNotNull($newUser->id);
        $this->assertNotEquals($user->id, $newUser->id);
    }

    public function test_employee_code_can_be_reused_after_soft_delete(): void
    {
        $user = User::factory()->create([
            'email' => 'first@test.com',
            'employee_code' => 'EMP-AAA-111',
        ]);
        $user->delete();

        $newUser = User::factory()->create([
            'email' => 'second@test.com',
            'employee_code' => 'EMP-AAA-111',
        ]);

        $this->assertNotNull($newUser->id);
    }

    public function test_active_email_cannot_be_duplicated(): void
    {
        User::factory()->create(['email' => 'active@test.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'active@test.com']);
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->delete();

        $this->post('/login', [
            'email' => 'deleted@test.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_soft_deleted_user_cannot_login_with_pin(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'employee_code' => 'EMP-DEL-001',
            'pin' => bcrypt('1234'),
            'pin_is_default' => false,
        ]);
        $user->delete();

        $this->post('/login/pin', [
            'employee_code' => 'EMP-DEL-001',
            'pin' => '1234',
        ]);

        $this->assertGuest();
    }
}
