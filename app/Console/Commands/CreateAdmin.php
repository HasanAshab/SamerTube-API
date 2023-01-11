<?php

namespace App\Console\Commands;

use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;
use App\Models\Admin;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new admin of the website';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      $admin = new Admin;
      $admin->name = $this->argument('name');
      $admin->email = $this->argument('email');
      $admin->password = bcrypt($this->argument('password'));
      if($admin->save()){
        $this->info("Successfully created new admin");
        return Command::SUCCESS;
      }
      $this->info("Failed to create new admin");
    }
}
