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
use Laravel\Sanctum\PersonalAccessToken;
use App\Notifications\CustomNotification;
use Illuminate\Support\Facades\Notification as Mail;
use Hash;
use DB;

class adminApi extends Controller
{

  public function dashboard() {
    $users = User::where('is_admin', 0);
    $total_users = $users->count();
    $users_analyticts = $users->select(DB::raw('DATE_FORMAT(DATE(created_at), "%d %b %Y") as date'), DB::raw('count(*) AS users'))->groupBy('date')->get();
    $tokens = PersonalAccessToken::where('last_used_at', '>=', now()->subMinute(2))->distinct('tokenable_id');
    $active_users_count = $tokens->count();
    $new_users_count = User::where('is_admin', 0)->whereDate('created_at', '>=', now()->subDays(2))->count();
    $total_admins = User::where('is_admin', 1)->count();
    $total_videos = Video::count();
    $total_posts = Post::count();
    $total_categories = Category::count();
    $total_reports = Report::count();
    return [
      'user' => [
        'total' => $total_users,
        'active_users' => $active_users_count,
        'new_users' => $new_users_count,
        'analytics' => $users_analyticts
      ],
      'total_admins' => $total_admins,
      'total_videos' => $total_videos,
      'total_posts' => $total_posts,
      'total_categories' => $total_categories,
      'total_reports' => $total_reports
    ];
  }

  // Get all users
  public function getUsers(Request $request) {
    $user_query = User::join('channels', 'channels.id', '=', 'users.id')->select('users.*', 'channels.name', 'channels.country', 'channels.total_subscribers', 'channels.total_videos', 'channels.logo_url')->orderByDesc('channels.total_subscribers')->where('is_admin', 0);
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $user_query->offset($offset)->limit($request->limit);
    }
    return $user_query->get();
  }

  // Get all active users
  public function getActiveUsers(Request $request) {
    $token_query = PersonalAccessToken::with('tokenable.channel')->where('last_used_at', '>=', now()->subMinute(2));
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $token_query->offset($offset)->limit($request->limit);
    }
    $tokens = $token_query->get();
    $users = collect();
    $tokens->each(function ($token) use ($users){
      $users->push($token->tokenable);
    });
    return $users;
  }

  // Get all new users
  public function getNewUsers(Request $request) {
    $user_query = User::join('channels', 'channels.id', '=', 'users.id')->select('users.*', 'channels.name', 'channels.country', 'channels.total_subscribers', 'channels.total_videos', 'channels.logo_url')->latest()->where('is_admin', 0)->whereDate('created_at', '>=', now()->subDays(2));
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $user_query->offset($offset)->limit($request->limit);
    }
    return $user_query->get();
  }

  // Get all admins
  public function getAdmins(Request $request) {
    $admin_query = User::join('channels', 'channels.id', '=', 'users.id')->select('users.*', 'channels.name', 'channels.country', 'channels.total_subscribers', 'channels.total_videos', 'channels.logo_url')->where('is_admin', 1)->whereNot('users.id', auth()->id());
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $admin_query->offset($offset)->limit($request->limit);
    }
    return $admin_query->get();
  }

  // Get all reports
  public function getReports(Request $request) {
    $report_query = Report::with('reportable')->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }
    return $report_query->get();
  }
  
  // Get all reports of a specific content
  public function getContentReports(Request $request, $type, $id) {
    $model = match($type){
      'video' => Video::class,
      'post' => Post::class,
      'channel' => Channel::class,
      'comment' => Comment::class,
      'reply' => Reply::class,
      default => null
    };
    $report_query = Report::where('reportable_type', $model)->where('reportable_id', $id)->latest();
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }
    return $report_query->get();
  }
  
  // Get top reported content
  public function getTopReportedContent(Request $request, $type){
    $model = match($type){
      'video' => Video::class,
      'post' => Post::class,
      'channel' => Channel::class,
      'comment' => Comment::class,
      'reply' => Reply::class,
      default => null
    };

    $report_query = Report::with('reportable')->select('reportable_id', 'reportable_type', DB::raw('count(*) as report_count'))->where('reportable_type', $model)->groupBy('reportable_id')->orderByDesc('report_count');
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }   
    return $report_query->get();
  }
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

  // Add new category of video
  public function addCategory(Request $request) {
    $request->validate([
      'name' => 'required|string|unique:categories',
    ]);
    $result = Category::create($request->only('name'));
    if ($result) {
      return ['success' => true,
        'message' => 'Category successfully added!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to add category!'], 451);
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

  // Delete category
  public function removeCategory($id) {
    if (Category::findOrFail($id)->delete()) {
      return ['success' => true,
        'message' => 'Category successfully deleted!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to delete category!'], 451);
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