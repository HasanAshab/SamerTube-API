<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Notification;
use App\Models\Subscriber;
use App\Models\Category;
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