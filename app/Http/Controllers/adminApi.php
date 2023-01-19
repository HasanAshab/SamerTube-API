<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Notification;
use App\Models\Subscriber;
use App\Models\Category;
use App\Models\Report;
use Hash;

class adminApi extends Controller
{
  
  public function dashboard(){
    $total_users = User::where('is_admin', 0)->count();
    $total_admins = User::where('is_admin', 1)->count();
    $total_videos = Video::query()->count();
    $total_categories = Category::query()->count();
    return [
      'success' => true,
      'total_users' => $total_users,
      'total_admins' => $total_admins,
      'total_videos' => $total_videos,
      'total_categories' => $total_categories,
    ];
  }
  
  // Get all users
  public function getUsers(){
    return User::where('is_admin', 0)->get();
  }
  
  // Get all admins
  public function getAdmins(){
    return User::where('is_admin', 1)->get();
  }
  
  // Get all Channels
  public function getChannels(){
    return Channel::all();
  }
  
  // Get all reports
  public function getReports($type){
    if($type === "image_or_title"){
      $reports = Report::where('type', $type)->join('videos', 'videos.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS video_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'videos.title AS reported_title', 'videos.thumbnail_url AS reported_thumnail_url'])->orderByDesc('reports.created_at')->get();
    }
    else if($type === "video"){
      $reports = Report::where('type', $type)->join('videos', 'videos.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS video_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'videos.video_url AS reported_video_url'])->orderByDesc('reports.created_at')->get();
    }
    else if($type === "comment"){
      $reports = Report::where('type', $type)->join('comments', 'comments.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS comment_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'comments.text AS reported_text'])->orderByDesc('reports.created_at')->get();
    }
    else if($type === "reply"){
      $reports = Report::where('type', $type)->join('replies', 'replies.id', '=', 'reports.for')->join('channels', 'channels.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS reply_id', 'channels.logo_url AS reporter_logo_url', 'channels.name AS reporter_name', 'reports.reason', 'replies.text AS reported_text'])->orderByDesc('reports.created_at')->get();
    }
    else if($type === "user"){
      $reports = Report::where('type', $type)->join('channels AS reported', 'reported.id', '=', 'reports.for')->join('channels AS reporter', 'reporter.id', '=', 'reports.user_id')->select(['reports.id', 'reports.for AS user_id', 'reporter.logo_url AS reporter_logo_url', 'reporter.name AS reporter_name', 'reports.reason', 'reported.logo_url AS reported_logo_url', 'reported.name AS reported_name'])->orderByDesc('reports.created_at')->get();
    }
    else{
      return null;
    }
    
    return $reports;
  }
  
  // Give a user 'admin' role
  public function makeAdmin($id){
    $user = User::find($id);
    $user->is_admin = 1;
    $user->tokens()->delete();
    if($user->save()){
      return ['success' => true, 'message' => 'Admin access granted to the user!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to make admin!'], 451);
  }
  
  // Add new category of video
  public function addCategory(Request $request){
    $request->validate([
      'category' => 'required|string|unique:categories',
    ]);
    $result = Category::create($validator->validated());
    if ($result){
      return ['success' => true, 'message' => 'Category successfully added!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to add category!'], 451);
  }
  
  // Delete category
  public function removeCategory($id){
    if (Category::findOrFail($id)->delete()){
      return ['success' => true, 'message' => 'Category successfully deleted!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to delete category!'], 451);
  }
  
  // Delete a user
  public function removeUser($id) {
    $user = User::findOrFail($id);
    if($user->is_admin){
      return accessDenied();
    }
    $result = $user->delete();
    $r2 = $user->videos->delete();
    $r3 = $user->channel->delete();
    $r5 = Subscriber::where('channel_id', $id)->delete();
    $r6 = Notification::where('from', $id)->delete();
    if ($result){
      return ['success' => true, 'message' => 'Account successfully deleted!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to delete account!'], 451);
  }
  

  // Hard delete a Notification for all users   
  public function removeNotification($id){
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