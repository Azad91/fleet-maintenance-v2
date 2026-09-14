<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MakesSuperAdminWithMfa;

class AssignmentAuditTransactionTest extends TestCase
{
    use MakesSuperAdminWithMfa;
    use RefreshDatabase;

    public function test_audit_failure_rolls_back_director_assignment(): void
    {
        // Disable Laravel's HTTP exception handler so the RuntimeException
        // thrown by the AuditLog listener propagates to the test instead
        // of being converted into a 500 response.
        $this->withoutExceptionHandling();

        $superAdmin = $this->makeSuperAdminWithMfa();
        $company = Company::factory()->create();

        $oldDirector = User::factory()->create(['role' => 'user', 'name' => 'Old Director']);
        $company->users()->attach($oldDirector->id, [
            'role' => 'director',
            'is_active' => true,
        ]);

        $newDirector = User::factory()->create(['role' => 'user', 'name' => 'New Director']);

        // Force AuditLog::create to throw during the transaction.
        AuditLog::creating(function () {
            throw new \RuntimeException('Simulated audit failure');
        });

        $caught = null;

        try {
            $this->actingAs($superAdmin)
                ->withSession(['current_company_id' => $company->id])
                ->post(route('super-admin.companies.assign-director', $company), [
                    'user_id' => $newDirector->id,
                ]);
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull(
            $caught,
            'Expected RuntimeException from the audit listener was not thrown'
        );
        $this->assertSame('Simulated audit failure', $caught->getMessage());

        // The old director must still be active — rollback worked.
        $oldPivot = $company->users()->whereKey($oldDirector->id)->first();
        $this->assertNotNull($oldPivot);
        $this->assertTrue(
            (bool) $oldPivot->pivot->is_active,
            'Old director must still be active after audit failure'
        );

        // No new director row should have been created.
        $this->assertNull(
            $company->users()->whereKey($newDirector->id)->first(),
            'New director must not be attached after audit failure'
        );
    }

    public function test_audit_failure_rolls_back_director_removal(): void
    {
        // Same reasoning as above.
        $this->withoutExceptionHandling();

        $superAdmin = $this->makeSuperAdminWithMfa();
        $company = Company::factory()->create();

        $director = User::factory()->create(['role' => 'user']);
        $company->users()->attach($director->id, [
            'role' => 'director',
            'is_active' => true,
        ]);

        AuditLog::creating(function () {
            throw new \RuntimeException('Simulated audit failure');
        });

        $caught = null;

        try {
            $this->actingAs($superAdmin)
                ->withSession(['current_company_id' => $company->id])
                ->delete(route('super-admin.companies.remove-director', [$company, $director]));
        } catch (\RuntimeException $e) {
            $caught = $e;
        }

        $this->assertNotNull(
            $caught,
            'Expected RuntimeException from the audit listener was not thrown'
        );
        $this->assertSame('Simulated audit failure', $caught->getMessage());

        // The director must still be attached — rollback worked.
        $this->assertNotNull(
            $company->users()->whereKey($director->id)->first(),
            'Director must still be attached after audit failure'
        );
    }
}
