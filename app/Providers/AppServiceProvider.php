<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use App\Models\Configuration;

class AppServiceProvider extends ServiceProvider
{
  /**
  * Register any application services.
  *
  * @return void
  */
  public function register() {
    //
  }

  /**
  * Bootstrap any application services.
  *
  * @return void
  */
  public function boot() {
    $app_configuration = cache()->get('config:app', function (){
      return Configuration::for('app');
    });
    $mail_configuration = cache()->get('config:mail', function (){
      return Configuration::for('mail');
    });
    $google_configuration = cache()->get('config:google', function (){
      return Configuration::for('google');
    });
    config([
      'app.name' => $app_configuration->name,
      'mail' => $mail_configuration,
      'services.google' => $google_configuration
    ]);
  }
}