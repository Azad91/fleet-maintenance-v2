<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintItem;
use App\Models\Garage;
use App\Services\GarageContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringComplaintsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Garage $garage;

    protected Bus $bus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->garage = Garage::factory()->create(['company_id' => $this->company->id]);
        $this->bus = Bus::factory()->create([
            'garage_id' => $this->garage->id,
            'company_id' => $this->company->id,
        ]);

        GarageContext::set($this->garage->id, $this->company->id);
    }

    protected function tearDown(): void
    {
        GarageContext::clear();
        parent::tearDown();
    }

    private function createComplaintWithItem(string $description, string $status = 'gözləmədə'): Complaint
    {
        $complaint = Complaint::create([
            'bus_id'     => $this->bus->id,
            'garage_id'  => $this->garage->id,
            'company_id' => $this->company->id,
            'yer'        => 'qaraj',
            'status'     => $status,
        ]);

        $complaint->items()->create([
            'description' => $description,
            'type'        => 'nasazliq',
        ]);

        return $complaint;
    }

    public function test_returns_empty_when_no_garage_context(): void
    {
        GarageContext::clear();

        $this->createComplaintWithItem('Test problem');
        $this->createComplaintWithItem('Test problem');

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(0, $results);
    }

    public function test_detects_recurring_issue_when_same_description_appears_twice(): void
    {
        $this->createComplaintWithItem('Əyləc nasazlığı');
        $this->createComplaintWithItem('Əyləc nasazlığı');

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Əyləc nasazlığı', $results->first()->description);
        $this->assertEquals(2, $results->first()->total);
    }

    public function test_ignores_single_occurrence(): void
    {
        $this->createComplaintWithItem('Tək problem');

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(0, $results);
    }

    public function test_excludes_closed_complaints(): void
    {
        $this->createComplaintWithItem('Problem A', 'həll olundu');
        $this->createComplaintWithItem('Problem A', 'həll olundu');

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(0, $results, 'Closed complaints must not be counted');
    }

    public function test_excludes_soft_deleted_complaints(): void
    {
        $c1 = $this->createComplaintWithItem('Soft delete testi');
        $this->createComplaintWithItem('Soft delete testi');

        $c1->delete(); // soft delete

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(0, $results, 'Soft-deleted complaints must not be counted');
    }

    public function test_excludes_complaints_from_other_garages(): void
    {
        $otherGarage = Garage::factory()->create(['company_id' => $this->company->id]);
        $otherBus = Bus::factory()->create([
            'garage_id' => $otherGarage->id,
            'company_id' => $this->company->id,
        ]);

        // Cari qarajda 1 dənə
        $this->createComplaintWithItem('Eyni problem');

        // Digər qarajda 1 dənə (eyni açıqlama)
        $otherComplaint = Complaint::create([
            'bus_id'     => $otherBus->id,
            'garage_id'  => $otherGarage->id,
            'company_id' => $this->company->id,
            'yer'        => 'qaraj',
            'status'     => 'gözləmədə',
        ]);
        $otherComplaint->items()->create([
            'description' => 'Eyni problem',
            'type'        => 'nasazliq',
        ]);

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(0, $results, 'Other garage complaints must not leak into current garage scope');
    }

    public function test_orders_by_most_recurring_first(): void
    {
        // "Problem B" — 3 dəfə
        $this->createComplaintWithItem('Problem B');
        $this->createComplaintWithItem('Problem B');
        $this->createComplaintWithItem('Problem B');

        // "Problem A" — 2 dəfə
        $this->createComplaintWithItem('Problem A');
        $this->createComplaintWithItem('Problem A');

        $results = ComplaintItem::recurring(30)->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Problem B', $results->first()->description);
        $this->assertEquals(3, $results->first()->total);
    }
}
