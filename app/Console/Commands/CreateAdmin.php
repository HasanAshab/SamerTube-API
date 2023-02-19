<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\URL;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Channel;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {name} {email} {country} {password}';

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
      $admin = new User;
      $admin->email = $this->argument('email');
      $admin->password = bcrypt($this->argument('password'));
      $admin->is_admin = 1;
      if($admin->save() && $this->createChannel($admin->id, $this->argument('name'), $this->argument('country'))){
        $admin->markEmailAsVerified();
        $this->info("Successfully created admin");
        return Command::SUCCESS;
      }
      $this->info("Failed to create admin");
    }
    
    protected function createChannel($id, $name, $country) {
      $channel = new Channel;
      $channel->name = $name;
      $channel->country = $country;
      return $channel->save();
    }
}
