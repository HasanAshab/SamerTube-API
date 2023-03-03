<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Post;
use App\Models\Notification;
use App\Models\Subscriber;
use App\Models\Category;
use App\Models\Report;
use App\Notifications\CustomNotification;
use Illuminate\Support\Facades\Notification as Mail;
use Hash;
use DB;

class AdminController extends Controller
{

  // Give a user 'admin' role
  public function makeAdmin($id) {
    $user = User::find($id);
    $user->is_admin = 1;
    $user->tokens()->delete();
    if ($user->save()) {
      return ['success' => true,
        'message' => 'Admin access granted to the user!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to make admin!'], 451);
  }

  // Make a new event on SamerTube
  public function sentNotification(Request $request) {
    $request->validate([
      'subject' => 'required',
      'description' => 'required',
      'greeting',
      'action_label',
      'action_url',
      'footer'
    ]);
    $users = User::where('is_admin', 0)->get();
    $result = Mail::send($users, new CustomNotification($request->all()));
    return ['success' => true, 'message' => 'Notification sent to all users!'];
  }

  // Delete a user
  public function removeUser($id) {
    $user = User::findOrFail($id);
    if ($user->is_admin) {
      return abort(405);
    }
    $result = $user->delete();
    $r3 = $user->channel()->delete();
    if ($result) {
      return ['success' => true,
        'message' => 'Account successfully deleted!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to delete account!'], 451);
  }


  // Hard delete a Notification for all users
  public function removeNotification($id) {
    $notification = Notification::findOrFail($id);
    if ($notification->delete()) {
      return ['success' => true,
        'message' => 'Notification successfully deleted for all users!'];
    }
    return response()->json(['success' => false,
      'message' => 'Failed to delete notification!'], 451);

  }

  // Clear Unimportant files from server storage
  protected function clear($path) {
    return unlink(storage_path("app/public/$path"));
  }
}