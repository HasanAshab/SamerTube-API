<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Listeners\SendNewUserJoinedNotificationToAdmins;
use App\Events\Searched;
use App\Listeners\PushSearchHistory;
use App\Events\Watched;
use App\Listeners\PushWatchHistory;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
  /**
  * The event to listener mappings for the application.
  *
  * @var array<class-string, array<int, class-string>>
  */
  protected $listen = [
    Registered::class => [
      SendEmailVerificationNotification::class,
      SendNewUserJoinedNotificationToAdmins::class,
    ],
    Searched::class => [
      PushSearchHistory::class,
    ],
    Watched::class => [
      PushWatchHistory::class,
    ],
  ];

  /**
  * Register any events for your application.
  *
  * @return void
  */
  public function boot() {
    //
  }

  /**
  * Determine if events and listeners should be automatically discovered.
  *
  * @return bool
  */
  public function shouldDiscoverEvents() {
    return false;
  }
}