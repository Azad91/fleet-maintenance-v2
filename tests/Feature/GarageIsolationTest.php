<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Models\Bus;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_buses_in_their_current_garage()
    {
        // 0. Əvvəlki testlərdən qalan konteksti təmizləyirik
        GarageContext::clear();

        // 1. Şirkət yarat
        $company = Company::factory()->create();

        // 2. İki qaraj yarat
        $garageA = Garage::factory()->create([
            'company_id' => $company->id,
        ]);
        $garageB = Garage::factory()->create([
            'company_id' => $company->id,
        ]);

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
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]);

        // 5. Session və Context-i tamamilə Qaraj A olaraq qururuq
        session([
            'current_garage_id' => $garageA->id,
            'current_company_id' => $company->id,
        ]);
        GarageContext::set($garageA->id, $company->id);

        $this->withoutMiddleware(\App\Http\Middleware\EnsureGarageSelected::class);

        // 6. Sorğu
        $response = $this->actingAs($user)->get(route('buses.index'));

        // 7. Yoxlama
        $response->assertStatus(200);
        $response->assertSee($busA->dqn);
        $response->assertDontSee($busB->dqn);
    }
}
