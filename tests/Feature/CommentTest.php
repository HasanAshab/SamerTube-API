<?php

use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Post;
use App\Models\Comment;

beforeEach(function() {
  $this->user = User::factory()->create();
  Channel::factory()->create(['id' => $this->user->id]);

  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create(['id' => $this->admin->id]);
});

test('User can reply on comment', function() {
  

});

// Video

// Post
test('User can comment on public post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'public'
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->user)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(204);
});

test('User can\'t comment on others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'scheduled'
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->user)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can comment on their scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->user)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(204);
});

test('Admin can comment on others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->admin)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(204);
});

test('User can\'t comment on shared post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'type' => 'shared',
    'shared_id' => 1,
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->user)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(405);
});


test('User can see comments on public post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'public'
  ]);
  Comment::factory()->createQuietly([
    'commenter_id' => $this->user->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->user)->getJson('/api/comment/post/'.$post->id);
  $response->assertStatus(200);
  $this->assertDatabaseCount('comments', 1);
});

test('User can\'t see comments on others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'scheduled'
  ]);
  Comment::factory()->createQuietly([
    'commenter_id' => $this->user->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->user)->getJson('/api/comment/post/'.$post->id);
  $response->assertStatus(405);
});

test('User can see comments on their scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  Comment::factory()->createQuietly([
    'commenter_id' => $this->admin->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->user)->getJson('/api/comment/post/'.$post->id);
  $response->assertStatus(200);
  $this->assertDatabaseCount('comments', 1);
});

test('Admin can see comments on others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  Comment::factory()->createQuietly([
    'commenter_id' => $this->user->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->admin)->getJson('/api/comment/post/'.$post->id);
  $response->assertStatus(200);
  $this->assertDatabaseCount('comments', 1);
});
