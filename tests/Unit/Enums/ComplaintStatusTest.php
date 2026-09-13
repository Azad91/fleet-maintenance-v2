<?php

namespace Tests\Unit\Enums;

use App\Enums\ComplaintStatus;
use PHPUnit\Framework\TestCase;

class ComplaintStatusTest extends TestCase
{
    // ==================================================================
    // 1. ENUM VALUES
    // ==================================================================

    public function test_enum_has_exactly_four_cases(): void
    {
        $this->assertCount(4, ComplaintStatus::cases());
    }

    public function test_enum_values_match_database_strings(): void
    {
        $this->assertSame('pending', ComplaintStatus::Pending->value);
        $this->assertSame('in_progress', ComplaintStatus::InProgress->value);
        $this->assertSame('completed', ComplaintStatus::Completed->value);
        $this->assertSame('cancelled', ComplaintStatus::Cancelled->value);
    }

    public function test_values_returns_all_values(): void
    {
        $this->assertSame(
            ['pending', 'in_progress', 'completed', 'cancelled'],
            ComplaintStatus::values()
        );
    }

    // ==================================================================
    // 2. OPEN / CLOSED CHECKS
    // ==================================================================

    public function test_pending_is_open(): void
    {
        $this->assertTrue(ComplaintStatus::Pending->isOpen());
    }

    public function test_in_progress_is_open(): void
    {
        $this->assertTrue(ComplaintStatus::InProgress->isOpen());
    }

    public function test_completed_is_not_open(): void
    {
        $this->assertFalse(ComplaintStatus::Completed->isOpen());
    }

    public function test_cancelled_is_not_open(): void
    {
        $this->assertFalse(ComplaintStatus::Cancelled->isOpen());
    }

    public function test_is_completed(): void
    {
        $this->assertTrue(ComplaintStatus::Completed->isCompleted());
        $this->assertFalse(ComplaintStatus::Pending->isCompleted());
    }

    public function test_is_cancelled(): void
    {
        $this->assertTrue(ComplaintStatus::Cancelled->isCancelled());
        $this->assertFalse(ComplaintStatus::Completed->isCancelled());
    }

    // ==================================================================
    // 3. TRANSITIONS
    // ==================================================================

    public function test_pending_can_transition_to_any_state(): void
    {
        $from = ComplaintStatus::Pending;

        $this->assertTrue($from->canTransitionTo(ComplaintStatus::Pending));
        $this->assertTrue($from->canTransitionTo(ComplaintStatus::InProgress));
        $this->assertTrue($from->canTransitionTo(ComplaintStatus::Completed));
        $this->assertTrue($from->canTransitionTo(ComplaintStatus::Cancelled));
    }

    public function test_in_progress_can_revert_to_pending(): void
    {
        $this->assertTrue(
            ComplaintStatus::InProgress->canTransitionTo(ComplaintStatus::Pending)
        );
    }

    public function test_completed_is_terminal(): void
    {
        $from = ComplaintStatus::Completed;

        $this->assertTrue($from->canTransitionTo(ComplaintStatus::Completed));
        $this->assertFalse($from->canTransitionTo(ComplaintStatus::Pending));
        $this->assertFalse($from->canTransitionTo(ComplaintStatus::InProgress));
        $this->assertFalse($from->canTransitionTo(ComplaintStatus::Cancelled));
    }

    public function test_cancelled_is_terminal(): void
    {
        $from = ComplaintStatus::Cancelled;

        $this->assertTrue($from->canTransitionTo(ComplaintStatus::Cancelled));
        $this->assertFalse($from->canTransitionTo(ComplaintStatus::Pending));
        $this->assertFalse($from->canTransitionTo(ComplaintStatus::InProgress));
        $this->assertFalse($from->canTransitionTo(ComplaintStatus::Completed));
    }

    // ==================================================================
    // 4. CREATABLE VALUES
    // ==================================================================

    public function test_creatable_values_excludes_terminal_states(): void
    {
        $creatable = ComplaintStatus::creatableValues();

        $this->assertSame(['pending', 'in_progress'], $creatable);
        $this->assertNotContains('completed', $creatable);
        $this->assertNotContains('cancelled', $creatable);
    }

    // ==================================================================
    // 5. TRYFROM — safe parsing
    // ==================================================================

    public function test_tryfrom_returns_null_for_invalid_value(): void
    {
        $this->assertNull(ComplaintStatus::tryFrom('invalid'));
        $this->assertNull(ComplaintStatus::tryFrom(''));
        $this->assertNull(ComplaintStatus::tryFrom('PENDING'));
    }

    public function test_tryfrom_returns_case_for_valid_value(): void
    {
        $this->assertSame(ComplaintStatus::Pending, ComplaintStatus::tryFrom('pending'));
        $this->assertSame(ComplaintStatus::Completed, ComplaintStatus::tryFrom('completed'));
    }
}
