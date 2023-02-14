<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class clearUploads extends Command
{
  /**
  * The name and signature of the console command.
  *
  * @var string
  */
  protected $signature = 'clear:uploads';

  /**
  * The console command description.
  *
  * @var string
  */
  protected $description = 'Clear all uploaded files from storage';

  /**
  * Execute the console command.
  *
  * @return int
  */
  public function handle() {
    $files = glob(storage_path('app/public/uploads').'/*'); // get all file names
    foreach ($files as $file) {
        unlink($file);
      $this->info("Clearing file: $file");
    }
      return Command::SUCCESS;
    }
  
  }