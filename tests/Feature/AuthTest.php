<?php
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

test('User can register manually', function () {
  $fake = Event::fake();
  DB::setEventDispatcher($fake);
  $data = [
    'name' => 'xyz',
    'email' => 'hostilarysten@gmail.com',
    'password' => 'password',
    'password_confirmation' => 'password'
  ];
  $response = $this->postJson('/api/auth/register', $data);
  Event::assertDispatched(Registered::class);
  $response->assertStatus(200);
  $this->assertDatabaseHas('users', ['email' => $data['email']]);
  $this->assertDatabaseHas('channels', ['name' => $data['name']]);
});

test('Admin account can be created from artisan', function () {
  Artisan::call('make:admin', [
    'name' => 'Hasan',
    'email' => 'admin@samertube.com' ,
    'country' => 'Bangladesh',
    'password' => 'password'
  ]);
  $this->assertDatabaseHas('users', ['is_admin' => true]);
  $this->assertDatabaseHas('channels', ['name' => 'Hasan']);
});



test('Verification link works', function () {
  $user = User::factory()->create();
  $verificationUrl = URL::temporarySignedRoute(
    'verification.verify',
    now()->addMinutes(60),
    ['id' => $user->id, 'hash' => sha1($user->email)]
  );
  $this->getJson($verificationUrl);
  $this->assertNotSame(null, $user->email_verified_at);
});

test('Users can login', function () {
  $user = User::factory()->create();
  $data = [
    'email' => $user->email,
    'password' => 'password'
  ];
  $response = $this->postJson('/api/auth/login', $data);
  $response->assertJsonStructure([
    'success',
    'access_token'
  ]);
});

test('Admins can login', function () {
  $admin = User::factory()->create(['is_admin' => true]);
  $data = [
    'email' => $admin->email,
    'password' => 'password'
  ];
  $response = $this->postJson('/api/auth/login', $data);
  $response->assertJsonStructure([
    'success',
    'access_token'
  ]);
});

test('Send reset password link', function () {
  Notification::fake();
  $user = User::factory()->create(['email' => 'hostilarysten@gmail.com']);
  $response = $this->postJson('/api/auth/forgot-password', ['email' => $user->email]);
  $response->assertStatus(200);
  Notification::assertSentTo(
    [$user],
    ResetPassword::class
  );
});


test('Reset password successfully', function () {
  $user = User::factory()->create();
  $token = Password::createToken($user);
  $newPassword = 'new_password';
  $response = $this->postJson("api/auth/reset-password", [
    'email' => $user->email,
    'password' => $newPassword,
    'password_confirmation' => $newPassword,
    'token' => $token
  ]);
  $response->assertStatus(200);
  $this->assertTrue(Hash::check($newPassword, User::find($user->id)->password));
});

test('Change password', function () {
  $user = User::factory()->create();
  $newPassword = 'new_password';
  $response = $this->actingAs($user)->postJson("api/auth/change-password", [
    'old_password' => 'password',
    'new_password' => $newPassword,
    'new_password_confirmation' => $newPassword,
  ]);
  $response->assertStatus(200);
  $this->assertTrue(Hash::check($newPassword, User::find($user->id)->password));
});

test('User can\'t access c-panel', function (){
  $user = User::factory()->create();
  $response = $this->actingAs($user)->getJson('/api/c-panel/dashboard');
  $response->assertStatus(403);
});

test('Get user details', function () {
  $user = User::factory()->create();
  $response = $this->actingAs($user)->getJson('/api/auth/profile');
  $response->assertStatus(200);
});

test('Check if user is admin', function () {
  $user = User::factory()->create();
  $admin = User::factory()->create(['is_admin' => true]);

  $this->actingAs($user)->getJson('/api/auth/is-admin')->assertJson([
    'success' => true,
    'data' => [
      'admin' => false
    ]
  ]);
$this->actingAs($admin)->getJson('/api/auth/is-admin')->assertJson([
  'success' => true,
  'data' => [
    'admin' => true
  ]
]);
});

test('Refresh access token', function () {
  $user = User::factory()->create();
  $token = $user->createToken('API TOKEN', ['user'])->plainTextToken;
  $response = $this->withHeaders([
      'Authorization' => 'Bearer '.$token
  ])->postJson('/api/auth/refresh');
  $response->assertStatus(200);
  $this->assertCount(1, $user->tokens);
});


test('Logout from account', function () {
  $user = User::factory()->create();
  $token = $user->createToken('API TOKEN', ['user'])->plainTextToken;
  $response = $this->withHeaders([
      'Authorization' => 'Bearer '.$token
  ])->postJson('/api/auth/logout');
  $response->assertStatus(200);
  $this->assertCount(0, $user->tokens);
});

test('Logout from all devices', function () {
  $user = User::factory()->create();
  $tokens = collect();
  for ($i = 0; $i < 3; $i++) {
    $token = $user->createToken('API TOKEN ', ['user']);
    $tokens->push($token);
  }
  $response = $this->withHeaders([
      'Authorization' => 'Bearer '.$tokens[0]->plainTextToken
  ])->postJson('/api/auth/logout-all');
  $response->assertStatus(200);
  $this->assertCount(0, $user->tokens);
});

test('Delete account', function () {
  $user = User::factory()->create();
  $response = $this->actingAs($user)->deleteJson('/api/auth/delete');
  $response->assertStatus(200);
  $this->assertDatabaseCount('users', 0);
});