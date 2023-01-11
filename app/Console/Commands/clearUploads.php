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
    $this->clearDir(storage_path('app/public/uploads/videos'));

    $this->clearDir(storage_path('app/public/uploads/thumbnails'));

      return Command::SUCCESS;
    }
    
  protected function clearDir($path){
    $files = array_diff(scandir($path), array('.', '..'));
    foreach ($files as $file) {
        unlink($path.'/'.$file);
      $this->info("Clearing file: $file");
    }
    
  }
  }