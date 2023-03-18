<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuration;

class ConfigController extends Controller
{
  // Create configuration for app
  public function appConfigure(Request $request) {
    $request->validate([
      'name' => 'required|max:25|min:2',
    ]);
    $configuration = Configuration::updateOrCreate(
      ['for' => 'app'],
      ['data' => $request->only('name')]
    );
    return $configuration
    ?['success' => true,
      'message' => 'App name changed successfully!']
    :response()->json(['success' => false, 'message' => 'Failed to change!'], 422);
  }

  // Create configuration for mail
  public function mailConfigure(Request $request) {
    $request->validate([
      'driver' => 'required',
      'host' => 'required',
      'port' => 'required|integer',
      'username' => 'required',
      'password' => 'required',
      'from_name' => 'required',
      'from_address' => 'required',
      'encryption' => 'required',
    ]);

    $data = [
      'driver' => $request->driver,
      'host' => $request->host,
      'port' => $request->port,
      'username' => $request->username,
      'password' => $request->password,
      'encryption' => $request->encryption,
      'from' => [
        'name' => $request->from_name,
        'address' => $request->from_address
      ],
    ];

    $configuration = Configuration::updateOrCreate(
      ['for' => 'mail'],
      ['data' => $data]
    );
    return $configuration
    ?['success' => true,
      'message' => 'Mail configuration changed successfully!']
    :response()->json(['success' => false, 'message' => 'Failed to change!'], 422);

  }

  // Create configuration for Google
  public function googleConfigure(Request $request) {
    $request->validate([
      'client_id' => 'required',
      'client_secret' => 'required',
      'redirect' => 'required'
    ]);
    $configuration = Configuration::updateOrCreate(
      ['for' => 'google'],
      ['data' => $request->only(['client_id', 'secret', 'redirect'])]
    );
    return $configuration
    ?['success' => true,
      'message' => 'Google configuration changed successfully!']
    :response()->json(['success' => false, 'message' => 'Failed to change!'], 422);
  }

  // Get specific Configuration data
  public function getConfigData($name) {
    return cache()->get("config:$name", function () {
      return Configuration::for ($name);
    });
  }
}