<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. FILLABLE YOXLAMASI
    // ==================================================================

    public function test_role_is_not_in_fillable(): void
    {
        $this->assertNotContains(
            'role',
            (new User)->getFillable(),
            'role must NOT be mass-assignable'
        );
    }

    public function test_default_role_is_user(): void
    {
        $user = new User;

        $this->assertEquals(
            RoleEnum::USER->value,
            $user->role,
            'New User instances must default to role=user'
        );
    }

    public function test_create_without_role_defaults_to_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'default@test.com',
            'password' => bcrypt('secret'),
        ]);

        $this->assertEquals(RoleEnum::USER->value, $user->fresh()->role);
    }

    // ==================================================================
    // 2. MASS ASSIGNMENT SPOOFING CƏHDİ
    // ==================================================================

    public function test_role_cannot_be_mass_assigned_to_super_admin(): void
    {
        $user = User::create([
            'name' => 'Attacker',
            'email' => 'attacker@test.com',
            'password' => bcrypt('secret'),
            'role' => RoleEnum::SUPER_ADMIN->value, // ← spoofing attempt
        ]);

        $this->assertEquals(
            RoleEnum::USER->value,
            $user->fresh()->role,
            'role=super_admin in mass-assignment payload must be ignored'
        );
    }

    public function test_update_cannot_mass_assign_role_to_super_admin(): void
    {
        $user = User::create([
            'name' => 'Regular',
            'email' => 'regular@test.com',
            'password' => bcrypt('secret'),
        ]);

        $user->update(['role' => RoleEnum::SUPER_ADMIN->value]);

        $this->assertEquals(
            RoleEnum::USER->value,
            $user->fresh()->role,
            'update([role => super_admin]) must be ignored'
        );
    }

    public function test_fill_role_is_ignored(): void
    {
        $user = new User;
        $user->fill(['role' => RoleEnum::SUPER_ADMIN->value]);

        $this->assertEquals(
            RoleEnum::USER->value,
            $user->role,
            'fill([role => ...]) must be ignored'
        );
    }

    // ==================================================================
    // 3. FORCEFILL / PROMOTE METODLARI
    // ==================================================================

    public function test_forcefill_can_set_role_explicitly(): void
    {
        $user = User::create([
            'name' => 'Forcefill',
            'email' => 'forcefill@test.com',
            'password' => bcrypt('secret'),
        ]);

        $user->forceFill(['role' => RoleEnum::SUPER_ADMIN->value])->save();

        $this->assertEquals(
            RoleEnum::SUPER_ADMIN->value,
            $user->fresh()->role
        );
    }

    public function test_promote_to_super_admin_helper_works(): void
    {
        $user = User::create([
            'name' => 'Promote',
            'email' => 'promote@test.com',
            'password' => bcrypt('secret'),
        ]);

        $user->promoteToSuperAdmin()->save();

        $this->assertEquals(
            RoleEnum::SUPER_ADMIN->value,
            $user->fresh()->role
        );
        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_demote_to_regular_user_helper_works(): void
    {
        $user = User::create([
            'name' => 'Demote',
            'email' => 'demote@test.com',
            'password' => bcrypt('secret'),
        ]);
        $user->promoteToSuperAdmin()->save();

        $user->demoteToRegularUser()->save();

        $this->assertEquals(RoleEnum::USER->value, $user->fresh()->role);
        $this->assertFalse($user->fresh()->isSuperAdmin());
    }

    // ==================================================================
    // 4. DB UNIQUE INDEX QORUMASI
    // ==================================================================

    public function test_only_one_active_super_admin_allowed(): void
    {
        // First super admin — OK
        User::create([
            'name' => 'First',
            'email' => 'first@test.com',
            'password' => bcrypt('secret'),
        ])->promoteToSuperAdmin()->save();

        // Second — must fail because of users_single_super_admin index
        $this->expectException(QueryException::class);

        User::create([
            'name' => 'Second',
            'email' => 'second@test.com',
            'password' => bcrypt('secret'),
        ])->promoteToSuperAdmin()->save();
    }

    // ==================================================================
    // 5. FACTORY HƏLƏ İŞLƏYİR
    // ==================================================================

    public function test_factory_can_still_create_super_admin(): void
    {
        // Factory uses Model::unguarded() internally — this must still work.
        $superAdmin = User::factory()->create(['role' => RoleEnum::SUPER_ADMIN->value]);

        $this->assertEquals(
            RoleEnum::SUPER_ADMIN->value,
            $superAdmin->fresh()->role
        );
    }
}
