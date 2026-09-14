<?php

namespace Tests\Unit\Enums;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleEnumLabelTest extends TestCase
{
    use RefreshDatabase;

    // ==================================================================
    // 1. LABELS RESOLVE IN EVERY SUPPORTED LOCALE
    // ==================================================================

    public function test_complaint_manager_label_resolves_in_english(): void
    {
        app()->setLocale('en');

        $label = RoleEnum::COMPLAINT_MANAGER->label();

        $this->assertSame('Complaint Manager', $label);
    }

    public function test_complaint_manager_label_resolves_in_azerbaijani(): void
    {
        app()->setLocale('az');

        $label = RoleEnum::COMPLAINT_MANAGER->label();

        $this->assertSame('Şikayət rəisi', $label);
    }

    public function test_complaint_manager_label_resolves_in_russian(): void
    {
        app()->setLocale('ru');

        $label = RoleEnum::COMPLAINT_MANAGER->label();

        $this->assertSame('Менеджер жалоб', $label);
    }

    public function test_complaint_manager_label_resolves_in_turkish(): void
    {
        app()->setLocale('tr');

        $label = RoleEnum::COMPLAINT_MANAGER->label();

        $this->assertSame('Şikayet Müdürü', $label);
    }

    // ==================================================================
    // 2. NO RAW KEY LEAKAGE
    // ==================================================================

    public function test_no_label_returns_the_raw_translation_key(): void
    {
        foreach (['en', 'az', 'ru', 'tr'] as $locale) {
            app()->setLocale($locale);

            foreach (RoleEnum::cases() as $case) {
                $label = $case->label();

                $this->assertStringNotContainsString(
                    'roles.',
                    $label,
                    "Role [{$case->value}] leaks the raw translation key in locale [{$locale}]"
                );

                $this->assertNotEmpty($label);
            }
        }
    }

    // ==================================================================
    // 3. FALLBACK BEHAVIOR
    // ==================================================================

    public function test_missing_translation_falls_back_to_title_case(): void
    {
        // Simulate a missing translation by switching to a locale that
        // has no roles.php file at all.
        app()->setLocale('xx');

        $label = RoleEnum::COMPLAINT_MANAGER->label();

        // Should return a title-cased version of the enum value, not
        // "roles.complaint_manager".
        $this->assertSame('Complaint Manager', $label);
    }

    // ==================================================================
    // 4. BULK LABELS MAP
    // ==================================================================

    public function test_labels_map_covers_every_enum_case(): void
    {
        app()->setLocale('en');

        $labels = RoleEnum::labels();

        foreach (RoleEnum::cases() as $case) {
            $this->assertArrayHasKey($case->value, $labels);
            $this->assertNotEmpty($labels[$case->value]);
        }
    }

    public function test_garage_role_labels_excludes_global_and_company_roles(): void
    {
        app()->setLocale('en');

        $garageLabels = RoleEnum::garageRoleLabels();

        // Global roles must not appear
        $this->assertArrayNotHasKey('super_admin', $garageLabels);
        $this->assertArrayNotHasKey('user', $garageLabels);

        // Company-level role must not appear
        $this->assertArrayNotHasKey('director', $garageLabels);

        // Garage roles must be present
        $this->assertArrayHasKey('admin', $garageLabels);
        $this->assertArrayHasKey('complaint_manager', $garageLabels);
    }
}
