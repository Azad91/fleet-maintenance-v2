<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_exactly_one_super_admin(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertSame(
            1,
            User::where('role', 'super_admin')->count()
        );
    }

    public function test_super_admin_email_and_password_are_dev_friendly(): void
    {
        $this->seed(UserSeeder::class);

        $superAdmin = User::where('email', 'admin@fleet.com')->first();
        $this->assertNotNull($superAdmin);
        $this->assertTrue($superAdmin->isSuperAdmin());
    }

    public function test_super_admin_is_not_attached_to_any_garage(): void
    {
        $this->seed(UserSeeder::class);

        $superAdmin = User::where('email', 'admin@fleet.com')->first();

        $this->assertSame(
            0,
            $superAdmin->garages()->count(),
            'SuperAdmin must not be attached to any garage'
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class); // twice

        $this->assertSame(
            1,
            User::where('email', 'admin@fleet.com')->count(),
            'Re-running the seeder must not duplicate the SuperAdmin'
        );
    }

    public function test_seeder_does_not_create_legacy_garage_admin(): void
    {
        $this->seed(UserSeeder::class);

        // The old seedGarageAdmin() created 'garage.admin@example.com'.
        // That account must no longer exist — garage admins are created
        // through the onboarding flow instead.
        $this->assertDatabaseMissing('users', [
            'email' => 'garage.admin@example.com',
        ]);
    }
}
