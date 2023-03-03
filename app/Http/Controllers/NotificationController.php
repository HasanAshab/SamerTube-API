<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
  // Get all notifications of a user
  public function getNotifications(Request $request) {
    $id = $request->user()->id;
    $subscriptions_id = Subscriber::where('subscriber_id',
      $id)->pluck('channel_id');
    $hidden_notifications_id = Hidden::where('user_id',
      $id)->pluck('notification_id');
    $notification_query = Notification::where(function ($query) use ($subscriptions_id) {
      $query->where('type', 'video')->whereIn('from', $subscriptions_id);
    })->orWhere(function ($query) use ($id) {
      $query->whereIn('type', ['comment', 'reply', 'heart', 'subscribe', 'like'])->where('for', $id);
    })->whereNotIn('id',
      $hidden_notifications_id)->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)
      ?$request->offset
      :0;
      $notification_query->offset($offset)->limit($request->limit);
    }
    $notifications = $notification_query->get();
    return $notifications;
  }

  // Hide a Notification
  public function hideNotification(Request $request, $notification_id) {
    $hidden = new Hidden;
    $hidden->notification_id = $notification_id;
    $result = $hidden->save();
    if ($result) {
      return ['success' => true,
        'message' => 'Notification successfully hided!'];
    }
    return response()->json([
      'success' => false,
      'message' => 'Failed to hide notification!'
    ], 422);
  }

*/

  // Sent notification to user
  protected function notify($emails, $data, $type) {
    if ($type === 'video') {
      foreach ($emails as $email) {
        Mail::to($email)->send(new VideoUploadedMail($data));
      }
    } else if ($type === 'comment') {
      Mail::to($emails)->send(new CommentedMail($data));
    } else if ($type === 'reply') {
      Mail::to($emails)->send(new RepliedMail($data));
    } else if ($type === 'liked') {
      Mail::to($emails)->send(new LikedMail($data));
    } else if ($type === 'heart') {
      Mail::to($emails)->send(new GotHeartMail($data));
    } else {
      return false;
    }
  }
}
