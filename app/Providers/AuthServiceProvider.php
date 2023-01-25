<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Video;
use App\Policies\CommentPolicy;
use App\Policies\ReplyPolicy;
use App\Policies\VideoPolicy;

class AuthServiceProvider extends ServiceProvider
{
  /**
  * The model to policy mappings for the application.
  *
  * @var array<class-string, class-string>
  */
  protected $policies = [
    Comment::class => CommentPolicy::class,
    Reply::class => ReplyPolicy::class,
    Video::class => VideoPolicy::class,
  ];

  /**
  * Register any authentication / authorization services.
  *
  * @return void
  */
  public function boot() {
    $this->registerPolicies();
    ResetPassword::createUrlUsing(function ($user, string $token) {
      return env('FRONT_URL') . '/reset-password?token=' . $token;
    });
  }
}