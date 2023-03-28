<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use App\Events\Searched;
use App\Events\Watched;
use App\Events\VideoUploaded;
use App\Events\Commented;
use App\Events\Replied;
use App\Events\Subscribed;
use App\Events\CommentHearted;
use App\Listeners\SendNewUserJoinedNotificationToAdmins;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Listeners\PushSearchHistory;
use App\Listeners\PushWatchHistory;
use App\Listeners\NotifyVideoToSubscribers;
use App\Listeners\NotifyCommentToCreator;
use App\Listeners\NotifyReplyToCommenter;
use App\Listeners\NotifySubscribeToCreator;
use App\Listeners\NotifyHeartToCommenter;
use App\Listeners\NotifyLikeToCommenter;

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
    VideoUploaded::class => [
      NotifyVideoToSubscribers::class,
    ],
    Commented::class => [
      NotifyCommentToCreator::class,
    ],
    CommentHearted::class => [
      NotifyHeartToCommenter::class,
    ],
    Replied::class => [
      NotifyReplyToCommenter::class,
    ],
    Subscribed::class => [
      NotifySubscribeToCreator::class,
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