<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Aquí había un migrate:fresh + db:seed cada hora, que borra todas las
        // tablas y las recrea vacías. Nunca se disparó porque el hosting
        // compartido no ejecuta schedule:run, pero habría destruido la base en
        // cuanto se configurara el cron estándar de Laravel en un servidor
        // propio. No agregar tareas destructivas aquí.
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
