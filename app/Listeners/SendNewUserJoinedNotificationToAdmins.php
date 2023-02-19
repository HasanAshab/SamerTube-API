<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewUserJoined;
use App\Models\User;

class SendNewUserJoinedNotificationToAdmins
{
  public function handle($event) {
    $admins = User::where('is_admin', 1)->get();
    Notification::send($admins, new NewUserJoined($event->user));
  }
}