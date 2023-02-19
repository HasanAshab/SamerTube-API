<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

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
    if (!env('USER_ACTIVE_STATUS', false)) {
      // Use customized personal access token model
      Sanctum::usePersonalAccessTokenModel(
        PersonalAccessToken::class
      );
    }
  }
}