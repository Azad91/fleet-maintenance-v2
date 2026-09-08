<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Garage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_are_soft_deleted_and_not_permanently_removed()
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['role' => 'super_admin']);

        // 1. Avtobus və Anbar detalı yaradırıq
        $bus = Bus::create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'dqn' => '99-TT-111',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'garage_id' => $garage->id,
            'company_id' => $company->id,
            'code' => 'W-001',
            'name' => 'Təkər',
            'quantity' => 10,
        ]);

        // 2. Controller vasitəsilə silinmə sorğuları göndəririk
        $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id
            ])
            ->delete(route('buses.destroy', $bus->id))
            ->assertRedirect();

        $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id
            ])
            ->delete(route('warehouses.destroy', $warehouse->id))
            ->assertRedirect();

        // 3. Bazadan fiziki olaraq silinmədiyini (Soft Delete olduğunu) yoxlayırıq
        $this->assertSoftDeleted('buses', ['id' => $bus->id, 'dqn' => '99-TT-111']);
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id, 'code' => 'W-001']);

        // 4. Normal axtarışda bu obyektlərin artıq tapılmadığını yoxlayırıq
        $this->assertNull(Bus::find($bus->id));
        $this->assertNull(Warehouse::find($warehouse->id));

        // 5. Obyektin bərpa (Restore) oluna bildiyini təsdiqləyirik
        $bus->restore();
        $this->assertNotNull(Bus::find($bus->id));
    }
}
