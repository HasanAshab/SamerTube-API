<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\clearNotifications;
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
       //$schedule->command('inspire')->hourly();
        $schedule->command(clearNotifications::class)->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
     protected $commands = [
      Commands\clearUploads::class,
      Commands\clearNotifications::class,
      Commands\CreateAdmin::class
    ];
     
    protected function commands()
    {
        $this->load(__DIR__.'/Commands/');

        require base_path('routes/console.php');
    }
}
