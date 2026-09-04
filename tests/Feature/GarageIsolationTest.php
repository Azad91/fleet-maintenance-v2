<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Models\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_see_buses_in_their_current_garage()
    {
        // 1. Şirkət və iki fərqli qaraj yaradırıq
        $company = Company::create(['name' => 'Test Şirkəti', 'slug' => 'test-sirketi', 'is_active' => true]);

        $garageA = Garage::create(['company_id' => $company->id, 'name' => 'Qaraj A', 'code' => 'QA-001', 'is_active' => true]);
        $garageB = Garage::create(['company_id' => $company->id, 'name' => 'Qaraj B', 'code' => 'QB-002', 'is_active' => true]);

        // 2. Hər qaraja bir avtobus əlavə edirik
        $busA = Bus::create(['garage_id' => $garageA->id, 'company_id' => $company->id, 'dqn' => '90-AA-111']);
        $busB = Bus::create(['garage_id' => $garageB->id, 'company_id' => $company->id, 'dqn' => '90-BB-222']);

        // 3. İstifadəçi yaradırıq və yalnız Qaraj A-ya təyin edirik
        $user = User::factory()->create(['role' => 'bus']); // Sistem səviyyəsində standart rol
        $user->garages()->attach($garageA, ['role' => 'admin', 'is_active' => true]); // Qaraj A-da admin

        // 4. Sessiyanı və Context-i Qaraj A kimi ayarlayırıq
        session([
            'current_garage_id' => $garageA->id,
            'current_company_id' => $company->id
        ]);
        \App\Services\GarageContext::set($garageA->id, $company->id);

        // 5. Avtobuslar səhifəsinə sorğu göndəririk
        $response = $this->actingAs($user)->get(route('buses.index'));

        // 6. Nəticələri yoxlayırıq: Səhifə açılmalı, Bus A görünməli, Bus B GÖRÜNMƏMƏLİDİR
        $response->assertStatus(200);
        $response->assertSee($busA->dqn);
        $response->assertDontSee($busB->dqn);
    }
}
