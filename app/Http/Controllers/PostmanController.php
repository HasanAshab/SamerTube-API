<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class PostmanController extends Controller
{
  public function index() {
    $users = User::query()->select('id', 'email')->get();
    return view('postman', ['users' => $users]);
  }

  public function getToken($id) {
    $user = User::find($id);
    $token = ($user->is_admin)
      ?$user->createToken("API TOKEN", ['admin'])->plainTextToken
      :$user->createToken("API TOKEN", ['user'])->plainTextToken;
    return ['token' => $token];
  }
}