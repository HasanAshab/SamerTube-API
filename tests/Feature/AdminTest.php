<?php
use App\Models\User;
use App\Models\Channel;
use App\Models\Category;

beforeEach(function() {
  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create(['id' => $this->admin->id]);
  $this->actingAs($this->admin);
});

test('Transform a user to admin', function () {
  $user = User::factory()->create(['is_admin' => 1]);
  $response = $this->postJson('api/c-panel/make-admin/'.$user->id);
  $response->assertStatus(200);
  $user->is_admin = 1;
  $this->assertDatabaseHas('users', $user->toArray());
});


test('Add category', function () {
  $response = $this->postJson('api/c-panel/category', ['name' => 'Gaming']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('categories', 1);
});

test('Remove category', function () {
  $category = Category::factory()->create();
  $response = $this->deleteJson('api/c-panel/category/'.$category->id);
  $response->assertStatus(200);
  $this->assertDatabaseCount('categories', 0);
});

test('Remove user', function () {
  $user = User::factory()->create();
  $response = $this->deleteJson('api/c-panel/user/'.$user->id);
  $response->assertStatus(200);
  $this->assertDatabaseMissing('users', $user->toArray());
});

test('Remove notification', function () {});

test('Dashboard returns currect data', function () {
  $response = $this->getJson('api/c-panel/dashboard');
  $response->assertStatus(200);
  $response->assertJsonStructure([
    'success',
    'data' => [
      'user' => [
        'total',
        'active_users',
        'new_users',
        'analytics'
      ],
      'total_admins',
      'total_videos',
      'total_posts',
      'total_categories',
      'total_reports',
    ]
  ]);
});

test('Get all users with pagination', function () {
  $users = User::factory(5)->create();
  $channels = Channel::factory(5)->create();
  $response = $this->getJson('api/c-panel/dashboard/users?offset=1&limit=2');
  $response->assertStatus(200);
  $response->assertJsonCount(2, 'data');
});

test('Get all active users with paginatiom', function () {});

test('Get all new users with paginatiom', function () {
  $oldUsers = User::factory(5)->create(['created_at' => now()->subDays(3)]);
  $oldChannels = Channel::factory(5)->create();
  $newUsers = User::factory(5)->create();
  $newChannels = Channel::factory(5)->create();
  $response = $this->getJson('api/c-panel/dashboard/users/new?offset=1&limit=2');
  $response->assertStatus(200);
  $response->assertJsonCount(2, 'data');
});


test('Get all admins with paginatiom', function () {
  $admins = User::factory(5)->create(['is_admin' => 1]);
  $channels = Channel::factory(5)->create();
  $response = $this->getJson('api/c-panel/dashboard/admins?offset=1&limit=2');
  $response->assertStatus(200);
  $response->assertJsonCount(2, 'data');
});

test('Reports', function () {});

test('Send notification to all users', function () {});