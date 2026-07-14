<?php

namespace Modules\Caja\Console\Commands;

use Illuminate\Console\Command;
use Modules\Caja\Models\PrintJob;
use Carbon\Carbon;

class CleanOldPrintJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'print:clean-old-jobs {--days=30 : Número de días de retención}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina trabajos de impresión antiguos ya completados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Limpiando jobs anteriores a: {$cutoffDate->toDateTimeString()}");

        $deleted = PrintJob::where('status', 'printed')
            ->where('printed_at', '<', $cutoffDate)
            ->delete();

        $this->info("✓ {$deleted} trabajos eliminados");

        // También limpiar jobs fallidos muy antiguos
        $failedDeleted = PrintJob::where('status', 'failed')
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        $this->info("✓ {$failedDeleted} trabajos fallidos eliminados");

        return Command::SUCCESS;
    }
}
