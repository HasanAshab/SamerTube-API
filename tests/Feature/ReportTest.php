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
  
  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create(['id' => $this->admin->id]);
});


test('User can report video', function () {
  $chategory = Category::factory()->create();
  $video = Video::factory()->createQuietly();
  $response = $this->actingAs($this->user)->postJson('api/report/video/'.$video->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});

test('User can report channel', function () {
  $response = $this->actingAs($this->user)->postJson('api/report/channel/'.$this->admin->id, ['reason' => 'This content is really harmful for child!']);
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
  $comment = Comment::factory()->createQuietly();
  $response = $this->actingAs($this->user)->postJson('api/report/comment/'.$comment->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});

test('User can report reply', function () {
  $reply = Reply::factory()->createQuietly();
  $response = $this->actingAs($this->user)->postJson('api/report/reply/'.$reply->id, ['reason' => 'This content is really harmful for child!']);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
});


test('Reporting a content multiple times updates report reason', function () {
  $chategory = Category::factory()->create();
  $video = Video::factory()->createQuietly();
  $data = ['reason' => 'This content is really harmful for child!'];
  $response = $this->actingAs($this->user)->postJson('api/report/video/'.$video->id, $data);
  $data['reason'] = 'This content is really very harmful for child!';
  $response = $this->actingAs($this->user)->postJson('api/report/video/'.$video->id, $data);
  $response->assertStatus(200);
  $this->assertDatabaseCount('reports', 1);
  $this->assertDatabaseHas('reports', $data);
});
