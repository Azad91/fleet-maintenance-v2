<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveAuditLogs extends Command
{
    protected $signature = 'audit:archive {--months=6 : Neçə aydan köhnə qeydlər arxivləşsin} {--dry-run : Yalnız neçə qeyd arxivlənəcəyini göstər, heç nə silmə}';

    protected $description = 'Köhnə audit qeydlərini arxivləşdirir';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoffDate = Carbon::now()->subMonths($months);
        $dryRun = $this->option('dry-run');

        $this->info("📅 {$months} aydan köhnə qeydlər arxivləşdiriləcək (Tarix: {$cutoffDate->format('Y-m-d H:i:s')})");

        // Arxivlənəcək qeydlərin sayı
        $count = AuditLog::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('✅ Arxivlənəcək heç bir qeyd yoxdur.');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  {$count} qeyd arxivlənəcək.");

        if ($dryRun) {
            $this->info('✅ Dry-run rejimi: heç nə silinmədi.');
            return Command::SUCCESS;
        }

        // Arxivləşdirmə
        $this->info('📦 Arxivləşdirmə başlayır...');

        DB::transaction(function () use ($cutoffDate) {
            // 1. Köhnə qeydləri arxiv cədvəlinə köçür
            DB::statement("
                INSERT INTO audit_log_archive (user_id, garage_id, company_id, auditable_type, auditable_id, event, old_values, new_values, created_at, archived_at)
                SELECT user_id, garage_id, company_id, auditable_type, auditable_id, event, old_values, new_values, created_at, NOW()
                FROM audit_logs
                WHERE created_at < ?
            ", [$cutoffDate]);

            // 2. Köhnə qeydləri sil
            DB::statement("DELETE FROM audit_logs WHERE created_at < ?", [$cutoffDate]);
        });

        $this->info('✅ Arxivləşdirmə tamamlandı!');
        $this->info("📊 Silinən qeyd sayı: {$count}");

        return Command::SUCCESS;
    }
}
