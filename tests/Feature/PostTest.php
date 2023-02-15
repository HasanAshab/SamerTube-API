<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Models\Channel;
use App\Models\File;
use App\Models\Post;
use App\Models\Poll;
use App\Models\Comment;
use App\Models\Review;
use App\Jobs\PublishPost;

beforeEach(function() {
  $this->user = User::factory()->create();
  Channel::factory()->create();

  $this->creator = User::factory()->create();
  Channel::factory()->create(['post_unlocked' => true]);

  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create();
});


test('User without unlocked post can\'t create post', function() {
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text',
    'visibility' => 'public',
  ];
  $response = $this->actingAs($this->user)->postJson('/api/post', $data);
  $response->assertStatus(405);
});

test('User with unlocked post can create post', function() {
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text',
    'visibility' => 'public',
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $response->assertStatus(200);
  $this->assertDatabaseHas('posts', $data);
});

test('Admin can create post at any condition', function() {
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text',
    'visibility' => 'public',
  ];
  $response = $this->actingAs($this->admin)->postJson('/api/post', $data);
  $response->assertStatus(200);
  $this->assertDatabaseHas('posts', $data);
});

test('User with unlocked post can share post', function() {
  $post = Post::factory()->createQuietly();
  $data = [
    'content' => 'Hello this is a shared post',
    'type' => 'shared',
    'visibility' => 'public',
    'shared_id' => $post->id,
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $response->assertStatus(200);
  $this->assertDatabaseHas('posts', $data);
});

test('User without unlocked post can\'t share post', function() {
  $post = Post::factory()->createQuietly();
  $data = [
    'content' => 'Hello this is a shared post',
    'type' => 'shared',
    'visibility' => 'public',
    'shared_id' => $post->id,
  ];
  $response = $this->actingAs($this->user)->postJson('/api/post', $data);
  $response->assertStatus(405);
  $this->assertDatabaseMissing('posts', $data);

});

test('Post can be scheduled', function() {
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text',
    'visibility' => 'scheduled',
    'publish_at' => date('Y-m-d H:i:s', strtotime(now()->addMinutes(2))),
  ];
  Queue::fake();
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $response->assertStatus(200);
  Queue::assertPushed(PublishPost::class, fn ($job) => !is_null($job->delay));
});

test('Post can attach images', function() {
  $totalFilesBefore = File::count();
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text',
    'visibility' => 'public',
    'images' => [UploadedFile::fake()->image('test1.jpg', 1), UploadedFile::fake()->image('test2.jpg', 1)]
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $totalFilesAfter = File::count();
  $response->assertStatus(200);
  $this->assertTrue($totalFilesBefore + 2 === $totalFilesAfter, 'Image was not saved!');
});

test('Post with polls', function() {
  $totalPostsBefore = Post::count();
  $totalPollsBefore = Poll::count();
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text_poll',
    'visibility' => 'public',
    'polls' => ['poll1', 'poll2']
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $totalPostsAfter = Post::count();
  $totalPollsAfter = Poll::count();
  $response->assertStatus(200);
  $this->assertTrue($totalPostsBefore + 1 === $totalPostsAfter, 'Post was not created!');
  $this->assertTrue($totalPollsBefore + 2 === $totalPollsAfter, 'Poll was not created!');
});

test('Polls can attach image', function() {
  $totalPostsBefore = Post::count();
  $totalPollsBefore = Poll::count();
  $totalFilesBefore = File::count();
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'image_poll',
    'visibility' => 'public',
    'polls' => ['poll1', 'poll2'],
    'poll_images' => [UploadedFile::fake()->image('poll_img1.jpg', 1), UploadedFile::fake()->image('poll_img2.jpg', 1)]
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $totalPostsAfter = Post::count();
  $totalPollsAfter = Poll::count();
  $totalFilesAfter = File::count();
  $response->assertStatus(200);
  $this->assertTrue($totalPostsBefore + 1 === $totalPostsAfter, 'Post was not created!');
  $this->assertTrue($totalPollsBefore + 2 === $totalPollsAfter, 'Poll was not created!');
  $this->assertTrue($totalFilesBefore + 2 === $totalFilesAfter, 'Image was not saved!');
});

test('User can see his all post', function() {
  $posts = Post::factory(10)->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $response = $this->actingAs($this->creator)->getJson('/api/posts/channel');
  $response->assertStatus(200);
});

test('User can\'t see others scheduled post', function() {
  Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  $response = $this->actingAs($this->user)->getJson('/api/posts/channel/'.$this->creator->id);
  $response->assertStatus(200);
  $response->assertJson([
    'success' => true,
    'data' => []
  ]);
});


test('Admin can see others scheduled posts', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  $response = $this->actingAs($this->admin)->getJson('/api/posts/channel/'. $this->creator->id);
  $response->assertStatus(200);
  $response->assertJson([
    'success' => true,
    'data' => [$post->toArray()]
  ]);
});

test('Post total votes resolved as rate and User can see which poll they are voted', function () {
  $posts = Post::factory(2)->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll',
    'visibility' => 'public',
    'total_votes' => 10
  ]);
  foreach ($posts as $post) {
    $polls = Poll::factory(2)->createQuietly([
      'post_id' => $post->id,
      'vote_count' => 5
    ]);
  }
  $response = $this->actingAs($this->user)->getJson('/api/posts/channel/'.$this->creator->id);
  $response->assertStatus(200);
  $response->assertJsonStructure([
    'success',
    'data' => [
      '*' => [
        'id',
        'channel_id',
        'type',
        'content',
        'visibility',
        'shared_id',
        'like_count',
        'dislike_count',
        'comment_count',
        'created_at',
        'edited',
        'polls' => [
          '*' => [
            'id',
            'name',
            'post_id',
            'image_url',
            'vote_rate',
            'voted'
          ]
        ]
      ]
    ]
  ]);
});

test('User can update his text post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text'
  ]);
  $data = ['content' => 'I updated that'];
  $response = $this->actingAs($this->creator)->putJson('/api/post/'.$post->id, $data);
  $response->assertStatus(200);
});

test('User can update his shared post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'shared'
  ]);
  $data = ['content' => 'I updated that'];
  $response = $this->actingAs($this->creator)->putJson('/api/post/'.$post->id, $data);
  $response->assertStatus(200);
});

test('User can\'t update his text poll post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll'
  ]);
  $data = ['content' => 'I updated that'];
  $response = $this->actingAs($this->creator)->putJson('/api/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can\'t update his image poll post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'image_poll'
  ]);
  $data = ['content' => 'I updated that'];
  $response = $this->actingAs($this->creator)->putJson('/api/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can\'t update others post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $data = ['content' => 'I updated that'];
  $response = $this->actingAs($this->user)->putJson('/api/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can delete his post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $response = $this->actingAs($this->creator)->deleteJson('/api/post/'.$post->id);
  $response->assertStatus(200);
});

test('User can\'t delete others post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $response = $this->actingAs($this->user)->deleteJson('/api/post/'.$post->id);
  $response->assertStatus(405);
});

test('Admin can delete others post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $response = $this->actingAs($this->admin)->deleteJson('/api/post/'.$post->id);
  $response->assertStatus(200);
});

test('User can review others public post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->user)->postJson('/api/review/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can review his scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->creator)->postJson('/api/review/post/'.$post->id, $data);
  $review = Review::first();
  $this->assertEquals($review->review, 1);
  $response->assertStatus(204);
});

test('Admin can review others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
    'type' => 'shared',
    'shared_id' => 1,
  ]);
  $data = ['review' => 1];
  $response = $this->actingAs($this->creator)->postJson('/api/review/post/'.$post->id, $data);
  $response->assertStatus(405);
});

test('User can see their review on a post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
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

test('User can comment on public post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(204);
});

test('Admin can comment on others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
    'type' => 'shared',
    'shared_id' => 1,
  ]);

  $data = [
    'text' => 'This is a test comment'
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/comment/post/'.$post->id, $data);
  $response->assertStatus(405);
});


test('User can see comments on public post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
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
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  Comment::factory()->createQuietly([
    'commenter_id' => $this->creator->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->creator)->getJson('/api/comment/post/'.$post->id);
  $response->assertStatus(200);
  $this->assertDatabaseCount('comments', 1);

});

test('Admin can see comments on others scheduled post', function() {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  Comment::factory()->createQuietly([
    'commenter_id' => $this->creator->id,
    'commentable_id' => $post->id,
    'commentable_type' => get_class($post),
    'text' => 'This is a test comment',
  ]);
  $response = $this->actingAs($this->admin)->getJson('/api/comment/post/'.$post->id);
  $response->assertStatus(200);
  $this->assertDatabaseCount('comments', 1);
});

test('User can\'t vote poll of own post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll',
    'visibility' => 'public'
  ]);
  $polls = Poll::factory(3)->createQuietly([
    'post_id' => $post->id
  ]);
  $response = $this->actingAs($this->creator)->postJson('/api/vote/'.$polls->first()->id);
  $response->assertStatus(405);
});

test('User can vote poll on others public post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll',
    'visibility' => 'public'
  ]);
  $polls = Poll::factory(3)->createQuietly([
    'post_id' => $post->id
  ]);
  $response = $this->actingAs($this->user)->postJson('/api/vote/'.$polls->first()->id);
  $response->assertStatus(204);
});

test('User can\'t vote poll on others scheduled post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll',
    'visibility' => 'scheduled'
  ]);
  $polls = Poll::factory(3)->createQuietly([
    'post_id' => $post->id
  ]);
  $response = $this->actingAs($this->user)->postJson('/api/vote/'.$polls->first()->id);
  $response->assertStatus(405);
});

test('Admin can vote poll on others scheduled post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll',
    'visibility' => 'scheduled'
  ]);
  $polls = Poll::factory(3)->createQuietly([
    'post_id' => $post->id
  ]);
  $response = $this->actingAs($this->admin)->postJson('/api/vote/'.$polls->first()->id);
  $response->assertStatus(204);
});

test('User can\'t vote multiple polls on same post', function () {
  $post = Post::factory()->createQuietly([
    'channel_id' => $this->creator->id,
    'type' => 'text_poll',
    'visibility' => 'public'
  ]);
  $polls = Poll::factory(3)->createQuietly([
    'post_id' => $post->id
  ]);
  foreach ($polls as $poll) {
    $this->actingAs($this->user)->postJson('/api/vote/'.$poll->id);
  }
  $this->assertDatabaseCount('votes', 1);
});