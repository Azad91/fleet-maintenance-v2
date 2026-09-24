<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveAuditLogs extends Command
{
    protected $signature = 'audit:archive {--months=6 : How many months of records to keep before archiving} {--dry-run : Only report how many rows would be archived, do not delete anything}';

    protected $description = 'Archive audit log entries older than the retention window';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoffDate = Carbon::now()->subMonths($months);
        $dryRun = $this->option('dry-run');

        $this->info("📅 Records older than {$months} months will be archived (cutoff: {$cutoffDate->format('Y-m-d H:i:s')})");

        // Number of rows that will be archived
        $count = AuditLog::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('✅ No records to archive.');

            return Command::SUCCESS;
        }

        $this->warn("⚠️  {$count} records will be archived.");

        if ($dryRun) {
            $this->info('✅ Dry-run mode: nothing was deleted.');

            return Command::SUCCESS;
        }

        // Archive
        $this->info('📦 Starting archive...');

        DB::transaction(function () use ($cutoffDate) {
            // 1. Copy old rows into the archive table
            DB::statement('
                INSERT INTO audit_log_archives (user_id, garage_id, company_id, auditable_type, auditable_id, event, old_values, new_values, original_created_at, archived_at)
                SELECT user_id, garage_id, company_id, auditable_type, auditable_id, event, old_values, new_values, created_at, NOW()
                FROM audit_logs
                WHERE created_at < ?
            ', [$cutoffDate]);

            // 2. Delete the old rows
            DB::statement('DELETE FROM audit_logs WHERE created_at < ?', [$cutoffDate]);
        });

        $this->info('✅ Archive complete.');
        $this->info("📊 Deleted records: {$count}");

        return Command::SUCCESS;
    }
}
