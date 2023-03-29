<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;

class SettingController extends Controller
{
  public function getSettings(){
    return auth()->user()->settings->data;
  }
  
  public function updateNotificationSettings(Request $request){
    $validated = $request->validate([
      'mail' => 'required|bool',
      'subscriptions' => 'required|bool',
      'channel' => 'required|bool',
      'replies' => 'required|bool',
      'shared_content' => 'required|bool',
    ]);
    $notifications_data = [];
    foreach ($validated as $key => $value){
      $notifications_data[$key] = (boolean)$value;
    }
    $settings = auth()->user()->settings;
    $data = $settings->data;
    $data->notifications = $notifications_data;
    $settings->data = $data;
    $result = $settings->save();
    return $result
      ?['success' => true, 'message' => 'Settings changed!']
      :['success' => false, 'message' => 'Failed to change settings!'];
  }
  
  public function updateAutoplaySettings(Request $request){
    $request->validate([
      'autoplay' => 'required|bool',
    ]);
    $settings = auth()->user()->settings;
    $data = $settings->data;
    $data->autoplay = (boolean) $request->autoplay;
    $settings->data = $data;
    $result = $settings->save();
    return $result
      ?['success' => true, 'message' => 'Settings changed!']
      :['success' => false, 'message' => 'Failed to change settings!'];
  }
}