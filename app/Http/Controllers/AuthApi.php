<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Video;
use App\Models\Channel;
use Validator;
use Illuminate\Auth\Events\Registered;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Hash;
use Socialite;

class AuthApi extends Controller
{

  // Create Account manually
  public function register(Request $request) {
    $request->validate([
      'name' => 'bail|required',
      'email' => 'bail|required|email|unique:users,email',
      'password' => 'bail|required|confirmed|min:8',
    ]);
    $user = User::create([
      'email' => $request->email,
      'password' => bcrypt($request->password)
    ]);
    $channel = Channel::create([
      'name' => $request->name,
      'logo_path' => 'assets/user_logo.png',
      'logo_url' => URL::signedRoute('file.serve', ['type' => 'logo', 'id' => $user->id]),
      'country' => 'Bangladesh'//Location::get($request->ip())->countryName);
    ]);
    if ($user && $channel) {
      event(new Registered($user));
      return ['success' => true,
        'message' => 'Your account is successfully created!'];
    }
    return response()->json(['success' => false,
      'message' => 'Failed to create account!'], 451);
  }

  // Login to Account manually
  public function login(Request $request) {
    $request->validate([
      'email' => 'bail|required|email',
      'password' => 'bail|required',
    ]);
    $user = User::where('email', $request->email)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json(['success' => false, 'message' => 'Credentials not match!'], 401);
    }
    $token = ($user->is_admin)
    ?$user->createToken("API TOKEN", ['admin'])->plainTextToken
    :$user->createToken("API TOKEN", ['user'])->plainTextToken;

    return ['success' => true,
      'access_token' => $token];
  }

  // Reditect user to select an owned email for register or login
  public function googleRedirect() {
    return Socialite::driver('google')->stateless()->redirect();
  }

  // Login or Create account using Google Socialite
  public function loginWithGoogle() {
    $user = Socialite::driver('google')->stateless()->user();
    $existingUser = User::where('email', $user->email)->first();
    if ($existingUser) {
      if ($existingUser->google_id === null) {
        $existingUser->google_id = $user->id;
        $existingUser->save();
      }
      $token = ($existingUser->is_admin)
      ?$existingUser->createToken("API TOKEN", ['admin'])->plainTextToken
      :$existingUser->createToken("API TOKEN", ['user'])->plainTextToken;
      return [
        'success' => true,
        'access_token' => $token
      ];
    } else {
      $createUser = User::create([
        'email' => $user->email,
        'google_id' => $user->id
      ]);
      $createUser->markEmailAsVerified();
      $createChannel = $this->createChannel($user->name, $user->avatar, 'Bangladesh'); //Location::get($request->ip())->countryName);

      if ($createUser and $createChannel) {
        return [
          'success' => true,
          'access_token' => $createUser->createToken("API TOKEN", ['user'])->plainTextToken
        ];
      }
      return response()->json(['success' => false,
        'message' => 'Failed to create account!'], 451);
    }
  }

  // Sent password reset link
  public function sentForgotPasswordLink(Request $request) {
    $request->validate(['email' => 'required|email']);
    $status = Password::sendResetLink($request->only('email'));
    return ['success' => true,
      'message' => 'Password reset link sent!'];
  }

  // Process password reset
  public function resetPassword(Request $request) {
    $request->validate([
      'token' => 'required',
      'email' => 'required|email',
      'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
      $request->only('email', 'password', 'password_confirmation', 'token'),
      function ($user, $password) {
        $user->forceFill([
          'password' => Hash::make($password)
        ])->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));
      }
    );
    return $status === Password::PASSWORD_RESET
    ?['success' => true,
      'message' => 'Password changed successfully!']
    :response()->json(['success' => true,
      'message' => 'Failed to reset password!'], 451);
  }

  // Change account Password
  public function changePassword(Request $request) {
    $request->validate([
      'old_password' => 'min:8',
      'new_password' => 'required|min:8|confirmed',
    ]);
    $user = User::find($request->user()->id);
    if (!$user->password == null) {
      if (Hash::check($request->new_password, $user->password)) {
        return response()->json(['success' => false, 'message' => 'New password shouldn\'t match to old one!'], 406);
      }
      if (!Hash::check($request->old_password, $user->password)) {
        return response()->json(['success' => false, 'message' => 'Old password don\'t match!'], 401);
      }
    }
    $user->password = Hash::make($request->new_password);
    if ($user->save()) {
      return ['success' => true,
        'message' => 'Password changed successfully'];
    }
    return response()->json(['success' => false, 'message' => 'Failed to change password!']);
  }
  // Create new token
  public function refresh(Request $request) {
    $user = $request->user();
    if ($user->currentAccessToken()->delete()) {
      $token = ($user->is_admin)
      ?$user->createToken("API TOKEN", ['admin'])->plainTextToken
      :$user->createToken("API TOKEN", ['user'])->plainTextToken;
      return [
        'success' => true,
        'access_token' => $token
      ];
    }
    return response()->json(['success' => false], 451);
  }

  // Logout a user
  public function logout(Request $request) {
    if ($request->user()->currentAccessToken()->delete()) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
  }

  // Logout from all devices
  public function logoutAllDevices(Request $request) {
    if ($request->user()->tokens()->delete()) {
      return ['success' => true];
    }
    return response()->json(['success' => false], 451);
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
    return response()->json(['success' => false,
      'message' => 'failed to delete account!'], 451);
  }

  protected function createChannel($name, $logo_url, $country) {
    $channel = new Channel;
    $channel->name = $name;
    $channel->logo_url = $logo_url;
    $channel->country = $country;
    return $channel->save();
  }
}