<?php

use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Post;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Reply;

beforeEach(function() {
  $this->user = User::factory()->create();
  Channel::factory()->create(['id' => $this->user->id]);
});


test('User can report video', function () {
  $chategory = Category::factory()->create();
  $video = Video::factory()->createQuietly();
  $response = $this->actingAs($this->user)->postJson('api/report/video/'.$video->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});

test('User can report channel', function () {
  $channel = Channel::factory()->create();
  $response = $this->actingAs($this->user)->postJson('api/report/channel/'.$channel->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});

test('User can report post', function () {
  $post = Post::factory()->createQuietly();
  $response = $this->actingAs($this->user)->postJson('api/report/post/'.$post->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});

test('User can report comment', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'public'
  ]);
  $comment = Comment::factory()->createQuietly([
    'commenter_id' => $this->user->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->user)->postJson('api/report/comment/'.$comment->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});

test('User can report reply', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'public'
  ]);
  $comment = Comment::factory()->createQuietly([
    'commenter_id' => $this->user->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $reply = Reply::factory()->createQuietly([
    'replier_id' => $this->user->id,
    'comment_id' => $comment->id,
    'text' => 'This is a test comment',
  ]);
  
  
  $response = $this->actingAs($this->user)->postJson('api/report/reply/'.$reply->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});


test('Reporting a content multiple times updates report reason', function () {
  $post = Post::factory()->createQuietly();
  $data = ['reason' => 'This content is really harmful for child!'];
  $response = $this->actingAs($this->user)->postJson('api/report/post/'.$post->id, $data);
  $data['reason'] = 'This content is really very harmful for child!';
  $response = $this->actingAs($this->user)->postJson('api/report/post/'.$post->id, $data);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
  $this->assertDatabaseHas('reports', $data);
});
