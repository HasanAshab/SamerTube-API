<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\clearNotifications;
use App\Console\Commands\UpdateAnalytics;
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(clearNotifications::class)->everyMinute();
        $schedule->command(UpdateAnalytics::class)->daily();
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
     protected $commands = [
      Commands\clearUploads::class,
      Commands\clearNotifications::class,
      Commands\CreateAdmin::class,
      Commands\UpdateAnalytics::class
    ];
     
    protected function commands()
    {
        $this->load(__DIR__.'/Commands/');

        require base_path('routes/console.php');
    }
}
