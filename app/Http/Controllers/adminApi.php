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
    $total_videos = Video::query()->count();
    $total_posts = Post::query()->count();
    $total_categories = Category::query()->count();
    $total_reports = Report::query()->count();
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

  // Get all users
  public function getActiveUsers(Request $request) {
    $token_query = PersonalAccessToken::where('last_used_at', '>=', now()->subMinute(2))->distinct('tokenable_id');
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $token_query->offset($offset)->limit($request->limit);
    }
    $tokens = $token_query->get();
    $active_users = collect();
    foreach ($tokens as $token) {
      $active_users->push($token->tokenable);
    }
    return $active_users;
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
  public function getReports(Request $request, $type) {
    if ($type === "image_or_title") {
      $report_query = Report::where('type', $type)->orderByDesc('reports.created_at')->join('videos', 'videos.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS video_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'videos.title AS reported_title', 'videos.thumbnail_url AS reported_thumnail_url', 'videos.link'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "video") {
      $report_query = Report::where('type', $type)->orderByDesc('reports.created_at')->join('videos', 'videos.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS video_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'videos.video_url AS reported_video_url', 'videos.link'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "comment") {
      $report_query = Report::where('type', $type)->orderByDesc('reports.created_at')->join('comments', 'comments.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS comment_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'comments.text AS reported_text'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "reply") {
      $report_query = Report::where('type', $type)->orderByDesc('reports.created_at')->join('replies', 'replies.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS reply_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'replies.text AS reported_text'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "user") {
      $report_query = Report::where('type', $type)->orderByDesc('reports.created_at')->join('channels AS reported', 'reported.id', '=', 'reports.for')->join('channels AS reporter', 'reporter.id', '=', 'reports.user_id')->select(['reports.id', 'reporter.logo_url AS reporter_logo_url', 'reporter.name AS reporter_name', 'reports.reason', 'reported.logo_url AS reported_logo_url', 'reported.name AS reported_name', 'reported.id AS reported_id'])->orderByDesc('reports.created_at')->get();
    }
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }
    return $report_query->get();
  }
  
  // Get all reports of a specific content
  public function getContentReports($type, $id) {
    if ($type === "image_or_title") {
      $report_query = Report::where('type', $type)->where('for', $id)->orderByDesc('reports.created_at')->join('videos', 'videos.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS video_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'videos.title AS reported_title', 'videos.thumbnail_url AS reported_thumnail_url', 'videos.link'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "video") {
      $report_query = Report::where('type', $type)->where('for', $id)->orderByDesc('reports.created_at')->join('videos', 'videos.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS video_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'videos.video_url AS reported_video_url', 'videos.link'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "comment") {
      $report_query = Report::where('type', $type)->where('for', $id)->orderByDesc('reports.created_at')->join('comments', 'comments.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS comment_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'comments.text AS reported_text'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "reply") {
      $report_query = Report::where('type', $type)->where('for', $id)->orderByDesc('reports.created_at')->join('replies', 'replies.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS reply_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'replies.text AS reported_text'])->orderByDesc('reports.created_at')->get();
    } else if ($type === "user") {
      $report_query = Report::where('type', $type)->where('for', $id)->orderByDesc('reports.created_at')->join('channels AS reported', 'reported.id', '=', 'reports.for')->join('channels AS reporter', 'reporter.id', '=', 'reports.user_id')->select(['reports.id', 'reporter.logo_url AS reporter_logo_url', 'reporter.name AS reporter_name', 'reports.reason', 'reported.logo_url AS reported_logo_url', 'reported.name AS reported_name', 'reported.id AS reported_id'])->orderByDesc('reports.created_at')->get();
    }
    if (isset($request->limit)) {
      $offset = isset($request->offset)?$request->offset:0;
      $report_query->offset($offset)->limit($request->limit);
    }
    return $report_query->get();
  }
  
  // Get top reported content
  public function getTopReportedContent($type){
    if($type === 'video'){
      $report_query = Report::whereIn('type', ['video', 'image_or_title'])->join('videos', 'videos.id', '=', 'reports.for')->select('reports.for AS video_id', 'videos.title', 'videos.link', 'videos.thumbnail_url', 'videos.video_url', 'reports.type', DB::raw('count(*) AS reports'))->groupBy('video_id', 'videos.title', 'videos.link', 'videos.thumbnail_url', 'videos.video_url', 'reports.type');
    }
    else if($type === 'comment'){
      $report_query = Report::where('type', 'comment')->join('comments', 'comments.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'comments.commenter_id')->select('reports.for AS comment_id', 'channels.name', 'channels.logo_url', 'comments.text', DB::raw('count(*) AS reports'))->groupBy('comment_id', 'channels.name', 'channels.logo_url', 'comments.text');
    }
    else if($type === 'reply'){
      $report_query = Report::where('type', 'reply')->join('replies', 'replies.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'replies.replier_id')->select('reports.for AS reply_id', 'channels.name', 'channels.logo_url', 'replies.text', DB::raw('count(*) AS reports'))->groupBy('reply_id', 'channels.name', 'channels.logo_url', 'replies.text');
    }
    else if($type === 'user'){
      $report_query = Report::where('type', 'user')->join('channels', 'channels.id', '=', 'reports.for')->select('reports.for AS user_id', 'channels.name', 'channels.logo_url', DB::raw('count(*) AS reports'))->groupBy('reports.for', 'channels.name', 'channels.logo_url');
    }
    return $report_query->orderByDesc('reports')->get();
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