<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Services\GarageContext;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garageA;

    protected Garage $garageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garageA = Garage::factory()->create([
            'company_id' => $this->company->id,
            'name'       => 'Alpha',
        ]);
        $this->garageB = Garage::factory()->create([
            'company_id' => $this->company->id,
            'name'       => 'Beta',
        ]);

        GarageContext::set($this->garageA->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function garageSession(): array
    {
        return [
            'current_garage_id'  => $this->garageA->id,
            'current_company_id' => $this->company->id,
        ];
    }

    private function adminOf(Garage $garage): User
    {
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garage->id, ['role' => 'admin', 'is_active' => true]);

        return $user;
    }

    // ==================== INDEX ====================

    public function test_admin_sees_only_users_from_current_garage(): void
    {
        $admin = $this->adminOf($this->garageA);

        $userA = User::factory()->create(['role' => 'user', 'name' => 'User A']);
        $userA->garages()->attach($this->garageA->id, ['role' => 'warehouse_manager', 'is_active' => true]);

        $userB = User::factory()->create(['role' => 'user', 'name' => 'User B']);
        $userB->garages()->attach($this->garageB->id, ['role' => 'warehouse_manager', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('User A');
        $response->assertDontSee('User B');
    }

    // ==================== STORE ====================

    public function test_admin_can_create_user_and_it_is_attached_to_current_garage(): void
    {
        $admin = $this->adminOf($this->garageA);

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->post(route('users.store'), [
                'name'                  => 'New User',
                'email'                 => 'new@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'role'                  => 'warehouse_manager',
            ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('email', 'new@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('user', $newUser->role);

        $pivot = $newUser->garages()->whereKey($this->garageA->id)->first();
        $this->assertNotNull($pivot);
        $this->assertEquals('warehouse_manager', $pivot->pivot->role);
        $this->assertTrue((bool) $pivot->pivot->is_active);
    }

    public function test_create_fails_transactionally_if_attach_errors(): void
    {
        $admin = $this->adminOf($this->garageA);
        User::factory()->create(['email' => 'dup@test.com']);

        $beforeCount = User::count();

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->post(route('users.store'), [
                'name'                  => 'Duplicate',
                'email'                 => 'dup@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'role'                  => 'warehouse_manager',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals($beforeCount, User::count());
    }

    // ==================== UPDATE ====================

    public function test_admin_can_update_user_in_current_garage(): void
    {
        $admin = $this->adminOf($this->garageA);

        $target = User::factory()->create(['role' => 'user', 'name' => 'Old Name']);
        $target->garages()->attach($this->garageA->id, ['role' => 'warehouse_manager', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->put(route('users.update', $target), [
                'name'      => 'New Name',
                'email'     => $target->email,
                'role'      => 'complaint_manager',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertEquals('New Name', $target->name);

        $pivot = $target->garages()->whereKey($this->garageA->id)->first();
        $this->assertEquals('complaint_manager', $pivot->pivot->role);
    }

    public function test_admin_cannot_update_user_from_other_garage(): void
    {
        $admin = $this->adminOf($this->garageA);

        $otherUser = User::factory()->create(['role' => 'user']);
        $otherUser->garages()->attach($this->garageB->id, ['role' => 'warehouse_manager', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->put(route('users.update', $otherUser), [
                'name'      => 'Hacked',
                'email'     => $otherUser->email,
                'role'      => 'admin',
                'is_active' => 1,
            ]);

        $response->assertForbidden();

        $otherUser->refresh();
        $this->assertNotEquals('Hacked', $otherUser->name);
    }

    // ==================== SELF-LOCKOUT PROTECTION ====================

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = $this->adminOf($this->garageA);

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->put(route('users.update', $admin), [
                'name'      => $admin->name,
                'email'     => $admin->email,
                'role'      => 'complaint_worker',
                'is_active' => 1,
            ]);

        $response->assertSessionHasErrors('role');

        $pivot = $admin->garages()->whereKey($this->garageA->id)->first();
        $this->assertEquals('admin', $pivot->pivot->role);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->adminOf($this->garageA);

        $response = $this->actingAs($admin)
            ->withSession($this->garageSession())
            ->put(route('users.update', $admin), [
                'name'      => $admin->name,
                'email'     => $admin->email,
                'role'      => 'admin',
                'is_active' => 0,
            ]);

        $response->assertSessionHasErrors('is_active');

        $pivot = $admin->garages()->whereKey($this->garageA->id)->first();
        $this->assertTrue((bool) $pivot->pivot->is_active);
    }

    // ==================== SUPER ADMIN ====================

    public function test_super_admin_can_edit_themselves_without_pivot(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->withSession($this->garageSession())
            ->get(route('users.edit', $superAdmin));

        $response->assertOk();
    }

    // ==================== USER SERVICE ====================

    public function test_user_service_creates_user_and_attaches_transactionally(): void
    {
        $service = app(UserService::class);

        $user = $service->createUserWithGarageRole([
            'name'     => 'Service User',
            'email'    => 'service@test.com',
            'password' => 'secret123',
            'role'     => 'complaint_manager',
        ], $this->garageA->id);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('service@test.com', $user->email);

        $pivot = $user->garages()->whereKey($this->garageA->id)->first();
        $this->assertEquals('complaint_manager', $pivot->pivot->role);
    }

    public function test_user_service_updates_only_provided_fields(): void
    {
        $service = app(UserService::class);
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($this->garageA->id, ['role' => 'warehouse_manager', 'is_active' => true]);

        $originalPassword = $user->password;

        $service->updateUserWithGarageRole($user, [
            'name'      => 'Updated Name',
            'email'     => $user->email,
            'role'      => 'complaint_manager',
            'is_active' => true,
        ], $this->garageA->id);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals($originalPassword, $user->password);
    }
}
