<?php
use App\Models\User;
use App\Models\Channel;
use App\Models\Video;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Review;
use App\Models\Category;

beforeEach(function() {
  $this->user = User::factory()->create();
  Channel::factory()->create(['id' => $this->user->id]);

  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create(['id' => $this->admin->id]);
});

// Video
test('User can review others public video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->admin->id,
    'visibility' => 'public'
  ]);
  
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/video/'.$video->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

test('User can\'t review others private video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->admin->id,
    'visibility' => 'private'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/video/'.$video->id, $data);
  $response->assertStatus(405);
});

test('User can\'t review others scheduled video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->admin->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/video/'.$video->id, $data);
  $response->assertStatus(405);
});

test('User can review his private video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->user->id,
    'visibility' => 'private'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/video/'.$video->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});


test('User can review his scheduled video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/video/'.$video->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

test('Admin can review others private video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->user->id,
    'visibility' => 'private'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->admin)->postJson('/api/review/video/'.$video->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});


test('Admin can review others scheduled video', function() {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->admin)->postJson('/api/review/video/'.$video->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

test('User can see their review on a video', function () {
  $category = Category::factory()->create();
  $video = Video::factory()->createQuietly([
    'category_id' => $category->id,
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  Review::factory()->createQuietly([
    'reviewer_id' => $this->user->id,
    'reviewable_id' => $video->id,
    'reviewable_type' => get_class($video),
    'review' => 1,
  ]);
  $response = $this->actingAs($this->user)->getJson('/api/review/video/'.$video->id);
  $response->assertStatus(200);
  $response->assertJson([
    'success' => true,
    'data' => [
    'review' => 1
    ]
  ]);
});

// Post
test('User can review others public post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'public'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/post/'.$post->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

test('User can\'t review others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can review his scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/post/'.$post->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

test('Admin can review others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->admin)->postJson('/api/review/post/'.$post->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});


test('User can\'t review shared post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'type' => 'shared',
    'shared_id' => 1,
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can see their review on a post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->user->id,
    'visibility' => 'public'
  ]);
  Review::factory()->createQuietly([
    'reviewer_id' => $this->user->id,
    'reviewable_id' => $post->id,
    'reviewable_type' => get_class($post),
    'review' => 1,
  ]);
  $response = $this->actingAs($this->user)->getJson('/api/review/post/'.$post->id);
  $response->assertStatus(200);
  $response->assertJson([
    'success' => true,
    'data' => [
    'review' => 1
    ]
  ]);
});

// Comment
test('User can review others comment', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'public'
  ]);
  $comment = Comment::factory()->createQuietly([
    'commenter_id' => $this->admin->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post)
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/comment/'.$comment->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

// Reply
test('User can review others reply', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->admin->id,
    'visibility' => 'public'
  ]);
  $comment = Comment::factory()->createQuietly([
    'commenter_id' => $this->admin->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post)
  ]);
  $reply = Reply::factory()->createQuietly([
    'replier_id' => $this->user->id,
    'comment_id' => $comment->id,
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/reply/'.$reply->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});
