<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace global unique constraints with soft-delete-aware partial
     * unique indexes on companies.slug and garages.code.
     *
     * PROBLEM
     * -------
     * The rest of the application already follows the pattern
     * "unique only among non-deleted rows" — see migrations
     * 2026_08_30_140000_fix_soft_delete_unique_indexes,
     * 2026_09_08_174319_replace_global_uniques_with_composite_indexes,
     * and 2026_09_13_071440_fix_user_soft_delete_unique_indexes.
     *
     * Two tables were left out of that pass:
     *   - companies.slug   (global UNIQUE)
     *   - garages.code     (global UNIQUE)
     *
     * CONSEQUENCE
     * -----------
     * A soft-deleted company or garage keeps its slug/code "occupied"
     * forever. A new company cannot reuse the slug, and the same
     * applies to garage codes. This is inconsistent with every other
     * tenant-scoped table in the codebase.
     *
     * FIX
     * ---
     * Replace the global unique constraints with partial unique
     * indexes that only apply to rows where deleted_at IS NULL.
     *
     * Matching application-side validation rules
     * (CompanyStoreRequest, CompanyUpdateRequest, GarageStoreRequest,
     * GarageUpdateRequest) also add ->whereNull('deleted_at').
     *
     * NOTE
     * ----
     * Restoring a soft-deleted company/garage whose slug/code is now
     * in use will fail with a unique violation. There is no restore
     * UI today, so this is only a design note for the future.
     */
    public function up(): void
    {
        // ── Drop global unique constraints ──
        DB::statement('ALTER TABLE companies DROP CONSTRAINT IF EXISTS companies_slug_unique');
        DB::statement('ALTER TABLE garages DROP CONSTRAINT IF EXISTS garages_code_unique');

        // ── Replace with partial unique indexes ──
        DB::statement('
            CREATE UNIQUE INDEX companies_slug_active_unique
            ON companies (slug)
            WHERE deleted_at IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX garages_code_active_unique
            ON garages (code)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS companies_slug_active_unique');
        DB::statement('DROP INDEX IF EXISTS garages_code_active_unique');

        DB::statement('ALTER TABLE companies ADD CONSTRAINT companies_slug_unique UNIQUE (slug)');
        DB::statement('ALTER TABLE garages ADD CONSTRAINT garages_code_unique UNIQUE (code)');
    }
};
