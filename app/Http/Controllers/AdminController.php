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
use Illuminate\Support\Facades\Config;


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
    
    return $result
      ?['success' => true, 'message' => 'Notification sent to all users!']
      :response()->json(['success' => false, 'message' => 'Failed to sent notification!'], 422);
  }
  
  // Change app name dynamically 
  public function changeAppName(Request $request){
    return Config::get('app.name');
    $request->validate([
      'name' => 'required|max:25|min:2'
    ]);
    $result = updateConfig(['app.name' => $request->name]);
    return $result
      ?['success' => true, 'message' => 'App name changed successfully!']
      :response()->json(['success' => false, 'message' => 'Failed to change app name!'], 422);
  }
  
  // Change mail settings dynamically
  public function changeMailSettings(Request $request) {
  $request->validate([
    'mailer' => 'required',
    'host' => 'required',
    'port' => 'required|integer',
    'username' => 'required',
    'password' => 'required',
    'from_name' => 'required',
    'encryption' => 'required',
  ]);
  $result = updateConfig([
    'MAIL_MAILER' => $request->mailer,
    'MAIL_HOST' => $request->host,
    'MAIL_PORT' => $request->port,
    'MAIL_USERNAME' => $request->username,
    'MAIL_PASSWORD' => $request->password,
    'MAIL_FROM_NAME' => $request->from_name,
    'MAIL_ENCRYPTION' => $request->encryption,
  ]);
  return $result;
  return $result
    ? ['success' => true, 'message' => 'Mail settings changed successfully!']
    : response()->json(['success' => false, 'message' => 'Failed to change mail settings!'], 422);
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