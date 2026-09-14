<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class ChangeDirectorTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    protected User $superAdmin;

    protected Company $company;

    protected Garage $garage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);

        $this->superAdmin = $this->makeSuperAdminWithMfa();
    }

    protected function asSuperAdmin(): self
    {
        return $this->actingAs($this->superAdmin)->withSession([
            'current_garage_id' => $this->garage->id,
            'current_company_id' => $this->company->id,
        ]);
    }

    protected function attachDirector(User $user, bool $isActive = true): void
    {
        $this->company->users()->attach($user->id, [
            'role' => 'director',
            'is_active' => $isActive,
        ]);
    }

    // ==================================================================
    // 1. HAPPY PATH — Director olmayan şirkətə təyin et
    // ==================================================================

    public function test_assigning_first_director_works(): void
    {
        $director = User::factory()->create(['role' => 'user']);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $director->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pivot = $this->company->users()->whereKey($director->id)->first();
        $this->assertNotNull($pivot);
        $this->assertSame('director', $pivot->pivot->role);
        $this->assertTrue((bool) $pivot->pivot->is_active);
    }

    // ==================================================================
    // 2. CHANGE — köhnə avtomatik deaktiv olur
    // ==================================================================

    public function test_changing_director_deactivates_previous_director(): void
    {
        $oldDirector = User::factory()->create(['role' => 'user', 'name' => 'Old Director']);
        $this->attachDirector($oldDirector);

        $newDirector = User::factory()->create(['role' => 'user', 'name' => 'New Director']);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $newDirector->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Köhnə director pivot-da qalır, amma is_active = false
        $oldPivot = $this->company->users()->whereKey($oldDirector->id)->first();
        $this->assertNotNull($oldPivot, 'Old director pivot must remain for audit trail');
        $this->assertSame('director', $oldPivot->pivot->role);
        $this->assertFalse((bool) $oldPivot->pivot->is_active);

        // Yeni director aktiv
        $newPivot = $this->company->users()->whereKey($newDirector->id)->first();
        $this->assertNotNull($newPivot);
        $this->assertSame('director', $newPivot->pivot->role);
        $this->assertTrue((bool) $newPivot->pivot->is_active);

        // Yalnız 1 aktiv director olmalıdır (DB constraint)
        $this->assertSame(1, $this->company->directors()->count());
    }

    public function test_only_one_active_director_exists_after_change(): void
    {
        $oldDirector = User::factory()->create(['role' => 'user']);
        $this->attachDirector($oldDirector);

        $newDirector = User::factory()->create(['role' => 'user']);

        $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $newDirector->id,
            ]);

        $activeCount = $this->company->users()
            ->wherePivot('role', 'director')
            ->wherePivot('is_active', true)
            ->count();

        $this->assertSame(1, $activeCount, 'Exactly one active director must remain');
    }

    // ==================================================================
    // 3. RE-ASSIGN — əvvəl deaktiv olunmuş user yenidən təyin edilir
    // ==================================================================

    public function test_reactivating_previously_deactivated_director(): void
    {
        $directorA = User::factory()->create(['role' => 'user']);
        $this->attachDirector($directorA, isActive: false);

        // Re-assign the same user
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $directorA->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pivot = $this->company->users()->whereKey($directorA->id)->first();
        $this->assertTrue((bool) $pivot->pivot->is_active);

        // Yalnız 1 pivot row (yenidən attach yox, updateExistingPivot işləyib)
        $this->assertSame(1, $this->company->users()
            ->wherePivot('role', 'director')
            ->count());
    }

    // ==================================================================
    // 4. ALREADY ACTIVE — xəta mesajı
    // ==================================================================

    public function test_assigning_user_who_is_already_active_director_fails_gracefully(): void
    {
        $director = User::factory()->create(['role' => 'user']);
        $this->attachDirector($director);

        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $director->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Vəziyyət dəyişməyib
        $pivot = $this->company->users()->whereKey($director->id)->first();
        $this->assertTrue((bool) $pivot->pivot->is_active);
    }

    // ==================================================================
    // 5. SUPER ADMIN BLOKLANIR
    // ==================================================================
    public function test_super_admin_cannot_be_assigned_as_director(): void
    {
        // setUp() already creates one super admin, and the DB partial
        // unique index `users_single_super_admin` forbids a second one.
        // So we reuse the existing super admin to test the guard.
        $response = $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $this->superAdmin->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // No pivot row must be created.
        $this->assertDatabaseMissing('company_user', [
            'user_id' => $this->superAdmin->id,
            'company_id' => $this->company->id,
        ]);
    }

    // ==================================================================
    // 6. AUDIT TRAIL — köhnə director tarixçədə qalır
    // ==================================================================

    public function test_deactivated_director_pivot_row_is_preserved(): void
    {
        $oldDirector = User::factory()->create(['role' => 'user']);
        $this->attachDirector($oldDirector);

        $newDirector = User::factory()->create(['role' => 'user']);

        $this->asSuperAdmin()
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $newDirector->id,
            ]);

        // Köhnə director silinməyib — yalnız deaktiv olub
        $this->assertDatabaseHas('company_user', [
            'user_id' => $oldDirector->id,
            'company_id' => $this->company->id,
            'role' => 'director',
            'is_active' => false,
        ]);
    }

    // ==================================================================
    // 7. NON-SUPER-ADMIN REJECTED
    // ==================================================================

    public function test_regular_user_cannot_change_director(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $target = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regular)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->post(route('super-admin.companies.assign-director', $this->company), [
                'user_id' => $target->id,
            ]);

        $response->assertForbidden();
    }
}
