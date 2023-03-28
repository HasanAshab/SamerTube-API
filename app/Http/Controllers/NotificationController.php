<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hidden;

class NotificationController extends Controller
{
  // Get all notifications of a user
  public function getNotifications() {
    $hiddens_id = auth()->user()->hiddens()->pluck('notification_id');
    $notifications = auth()->user()->notifications()->whereNotIn('id', $hiddens_id)->get();
    auth()->user()->unreadNotifications->markAsRead();
    return $notifications;
  }

  // Get the count of unread notification
  public function getUnreadNotificationCount(){
    return auth()->user()->unreadNotifications()->count();
  }

  // Hide a Notification
  public function hideNotification($notification_id) {
    $hidden = Hidden::create([
      'notification_id' => $notification_id
    ]);
    if ($hidden) {
      return ['success' => true,
        'message' => 'Notification successfully hided!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to hide notification!'
    ], 422);
  }
}
