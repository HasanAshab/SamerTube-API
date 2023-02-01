<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Channel;
use Validator;
use Illuminate\Auth\Events\Registered;
use Stevebauman\Location\Facades\Location;
use Hash;
use Socialite;

class userAuthApi extends Controller
{

  /**
  * Get a Token via given credentials.
  *
  * @return \Illuminate\Http\JsonResponse
  */

  public function googleRedirect() {
    return Socialite::driver('google')->stateless()->redirect();
  }


  public function loginWithGoogle() {
    $user = Socialite::driver('google')->stateless()->user();
    $existingUser = User::where('google_id', $user->id)->first();
    if ($existingUser) {
      return [
        'success' => true,
        'access_token' => $existingUser->createToken("API TOKEN", ['user'])->plainTextToken
      ];
    } else {
      $createUser = User::create([
        'email' => $user->email,
        'google_id' => $user->id
      ]);
      $createChannel = $this->createChannel($user->name, $user->avatar, 'Bangladesh'); //Location::get($request->ip())->countryName);

      if ($createUser and $createChannel) {
        return [
          'success' => true,
          'access_token' => $createUser->createToken("API TOKEN", ['user'])->plainTextToken
        ]; 
      }
      return ['success' => false,
        'message' => 'Failed to create account!'];
    }
  }
  
 // Create new token
  public function refresh(Request $request) {
    if ($request->user()->currentAccessToken()->delete()) {
      return ['success' => true,
        'access_token' => $request->user()->createToken("API TOKEN", ['user'])->plainTextToken];
    }
    return ['success' => false];
  }


  // Logout a user
  public function logout(Request $request) {
    if ($request->user()->currentAccessToken()->delete()) {
      return ['success' => true];
    }
    return ['success' => false];
  }

  // Logout from all devices
  public function logoutAllDevices(Request $request) {
    if ($request->user()->tokens()->delete()) {
      return ['success' => true];
    }
    return ['success' => false];
  }

  // Delete a user
  public function destroy(Request $request) {
    $id = $request->user()->id;
    $result = User::find($id)->delete();
    $r2 = Video::where('uploader_id', $id)->delete();
    $channel = Channel::find($id);
    $channel->delete();
    $r4 = Comment::where('user_id', $id)->delete();
    $r5 = Subscriber::where('channel_id', $id)->delete();
    $r6 = Notification::where('from', $id)->delete();
    if ($result) {
      unlink(storage_path("app/public/".$channel->logo));
      return ['success' => true,
        'message' => 'Your account is successfully deleted!'];
    }
    return ['success' => false,
      'message' => 'failed to delete account!'];
  }

  protected function createChannel($name, $logo_url, $country) {
    $channel = new Channel;
    $channel->name = $name;
    $channel->logo_url = $logo_url;
    $channel->country = $country;
    return $channel->save();
  }
}