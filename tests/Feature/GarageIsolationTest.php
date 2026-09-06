<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Models\Bus;
use App\Models\Complaint;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_buses_in_their_current_garage()
    protected function setUp(): void
    {
        // 0. Əvvəlki testlərdən qalan konteksti təmizləyirik
        parent::setUp();
        GarageContext::clear();
    }

        // 1. Şirkət yarat
    public function test_user_can_only_see_buses_in_their_current_garage_with_real_middleware()
    {
        $company = Company::factory()->create();

        // 2. İki qaraj yarat
        $garageA = Garage::factory()->create([
            'company_id' => $company->id,
        ]);
        $garageB = Garage::factory()->create([
            'company_id' => $company->id,
        ]);
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        // 3. Hər qaraja bir avtobus əlavə et
        $busA = Bus::factory()->create([
            'garage_id' => $garageA->id,
            'company_id' => $company->id,
            'dqn' => '90-AA-111',
        ]);
        $busB = Bus::factory()->create([
            'garage_id' => $garageB->id,
            'company_id' => $company->id,
            'dqn' => '90-BB-222',
        ]);

        // 4. İstifadəçi yarat və Qaraj A-ya təyin et
        $user = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

        // 5. Session və Context-i tamamilə Qaraj A olaraq qururuq
        session([
            'current_garage_id' => $garageA->id,
            'current_company_id' => $company->id,
        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageA->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        $response->assertStatus(200);
        $response->assertSee($busA->dqn);
        $response->assertDontSee($busB->dqn);
    }

    public function test_user_cannot_access_unauthorized_garage_session()
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

        // İstifadəçi özü olmayan Qaraj B-ni session-a qoyub daxil olmağa çalışır
        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageB->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        // Middleware bunu aşkar edib garage.selection-a yönləndirməlidir
        $response->assertRedirect(route('garage.selection'));
    }

    public function test_user_cannot_view_or_edit_bus_from_another_garage_idor()
    {
        $company = Company::factory()->create();
        $garageA = Garage::factory()->create(['company_id' => $company->id]);
        $garageB = Garage::factory()->create(['company_id' => $company->id]);

        $busB = Bus::factory()->create([
            'garage_id' => $garageB->id,
            'company_id' => $company->id,
            'dqn' => '90-BB-999',
        ]);
        GarageContext::set($garageA->id, $company->id);

        $this->withoutMiddleware(\App\Http\Middleware\EnsureGarageSelected::class);
        $user = User::factory()->create(['role' => 'user']);
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

        // 6. Sorğu
        $response = $this->actingAs($user)->get(route('buses.index'));
        // Qaraj A-da olan istifadəçi Qaraj B-nin avtobusunu redaktə etməyə çalışır
        $response = $this->actingAs($user)
            ->withSession([
                'current_garage_id' => $garageA->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.edit', $busB));

        // 7. Yoxlama
        // HasGarageScope və ya Policy səbəbindən 404/403 qaytarmalıdır
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_super_admin_can_access_any_garage()
    {
        $company = Company::factory()->create();
        $garage = Garage::factory()->create(['company_id' => $company->id]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)
            ->withSession([
                'current_garage_id' => $garage->id,
                'current_company_id' => $company->id,
            ])
            ->get(route('buses.index'));

        $response->assertStatus(200);
        $response->assertSee($busA->dqn);
        $response->assertDontSee($busB->dqn);
    }
}

