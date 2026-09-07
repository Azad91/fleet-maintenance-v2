<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Garage;
use App\Models\Company;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Garage $garage;
    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->user = User::factory()->create();
        $this->user->garages()->attach($this->garage->id, ['role' => 'admin', 'is_active' => true]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    public function test_profile_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->user->refresh();

        $this->assertSame('Test User', $this->user->name);
        $this->assertSame('test@example.com', $this->user->email);
        $this->assertNull($this->user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $this->user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($this->user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($this->user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->withSession([
                'current_garage_id' => $this->garage->id,
                'current_company_id' => $this->company->id,
            ])
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($this->user->fresh());
    }
}
