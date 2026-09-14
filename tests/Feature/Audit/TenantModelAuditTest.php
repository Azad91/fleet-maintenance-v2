<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\ComplaintType;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that the Auditable trait now covers the tenant-level
 * models (Company, Garage, User, ComplaintType).
 */
class TenantModelAuditTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. COMPANY
    // ==================================================================

    public function test_company_creation_is_audited(): void
    {
        $company = Company::create([
            'name' => 'Audit Test Co',
            'slug' => 'audit-test-co',
            'is_active' => true,
        ]);

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Audit Test Co', $log->new_values['name']);
        // Company audit logs carry their own id as company_id.
        $this->assertEquals($company->id, $log->company_id);
    }

    public function test_company_update_is_audited(): void
    {
        $company = Company::create([
            'name' => 'Original',
            'slug' => 'original',
            'is_active' => true,
        ]);

        AuditLog::query()->delete();

        $company->update(['name' => 'Renamed', 'is_active' => false]);

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Original', $log->old_values['name']);
        $this->assertEquals('Renamed', $log->new_values['name']);
        $this->assertTrue($log->old_values['is_active'] === true || $log->old_values['is_active'] === '1');
    }

    public function test_company_deletion_is_audited(): void
    {
        $company = Company::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'is_active' => true,
        ]);

        AuditLog::query()->delete();

        $company->delete();

        $log = AuditLog::where('auditable_type', Company::class)
            ->where('auditable_id', $company->id)
            ->where('event', 'deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('To Delete', $log->old_values['name']);
    }

    // ==================================================================
    // 2. GARAGE
    // ==================================================================

    public function test_garage_creation_is_audited(): void
    {
        $company = Company::factory()->create();

        $garage = Garage::create([
            'company_id' => $company->id,
            'name' => 'Audit Garage',
            'code' => 'AG-001',
            'is_active' => true,
        ]);

        $log = AuditLog::where('auditable_type', Garage::class)
            ->where('auditable_id', $garage->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Audit Garage', $log->new_values['name']);
        // Garage audit logs carry their own id as garage_id.
        $this->assertEquals($garage->id, $log->garage_id);
        $this->assertEquals($company->id, $log->company_id);
    }

    public function test_garage_deactivation_is_audited(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        AuditLog::query()->delete();

        $garage->update(['is_active' => false]);

        $log = AuditLog::where('auditable_type', Garage::class)
            ->where('auditable_id', $garage->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('is_active', $log->new_values);
    }

    // ==================================================================
    // 3. USER
    // ==================================================================

    public function test_user_creation_is_audited(): void
    {
        $user = User::create([
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => bcrypt('secret'),
        ]);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('newuser@test.com', $log->new_values['email']);
    }

    public function test_user_password_is_not_audited(): void
    {
        $user = User::create([
            'name' => 'Secret Keeper',
            'email' => 'secret@test.com',
            'password' => bcrypt('secret'),
        ]);

        AuditLog::query()->delete();

        $user->update(['password' => bcrypt('new-secret')]);

        // No audit log should be created because password is the only
        // field that changed and it is excluded from audit.
        $logs = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->get();

        $this->assertCount(0, $logs, 'Password change alone must not generate an audit log');
    }

    public function test_user_pin_is_not_audited(): void
    {
        $user = User::create([
            'name' => 'PIN Keeper',
            'email' => 'pin@test.com',
            'password' => bcrypt('secret'),
            'pin' => bcrypt('1234'),
        ]);

        AuditLog::query()->delete();

        $user->update(['pin' => bcrypt('5678')]);

        $logs = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->get();

        $this->assertCount(0, $logs, 'PIN change alone must not generate an audit log');
    }

    public function test_user_role_change_is_audited(): void
    {
        $user = User::create([
            'name' => 'Promote Me',
            'email' => 'promote@test.com',
            'password' => bcrypt('secret'),
        ]);

        AuditLog::query()->delete();

        $user->forceFill(['role' => 'super_admin'])->save();

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('user', $log->old_values['role']);
        $this->assertEquals('super_admin', $log->new_values['role']);
    }

    // ==================================================================
    // 4. COMPLAINT TYPE
    // ==================================================================

    public function test_complaint_type_creation_is_audited(): void
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $type = ComplaintType::withoutGlobalScopes()->create([
            'name' => 'Audited Type',
            'garage_id' => $garage->id,
            'company_id' => $company->id,
        ]);

        $log = AuditLog::where('auditable_type', ComplaintType::class)
            ->where('auditable_id', $type->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Audited Type', $log->new_values['name']);
    }
}
