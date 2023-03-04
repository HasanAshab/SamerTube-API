<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Models\User;
use App\Models\Channel;
use App\Models\File;
use App\Models\Post;
use App\Models\Poll;
use App\Jobs\PublishPost;
use Illuminate\Support\Facades\Artisan;

beforeEach(function() {
  $this->user = User::factory()->create();
  Channel::factory()->create(['id' => $this->user->id]);

  $this->creator = User::factory()->create();
  Channel::factory()->create(['id' => $this->creator->id, 'post_unlocked' => true]);

  $this->admin = User::factory()->create(['is_admin' => 1]);
  Channel::factory()->create(['id' => $this->admin->id]);
});

afterEach(function (){
  Artisan::call('clear:uploads');
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
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text',
    'visibility' => 'public',
    'images' => [UploadedFile::fake()->image('test1.jpg', 1), UploadedFile::fake()->image('test2.jpg', 1)]
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $response->assertStatus(200);
  $this->assertDatabaseCount('posts', 1);
  $this->assertDatabaseCount('files', 2);
});

test('Post with polls', function() {
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'text_poll',
    'visibility' => 'public',
    'polls' => ['poll1', 'poll2']
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $response->assertStatus(200);
  $this->assertDatabaseCount('posts', 1);
  $this->assertDatabaseCount('polls', 2);
});

test('Polls can attach image', function() {
  $data = [
    'content' => 'Hello this is a test post',
    'type' => 'image_poll',
    'visibility' => 'public',
    'polls' => ['poll1', 'poll2'],
    'poll_images' => [UploadedFile::fake()->image('poll_img1.jpg', 1), UploadedFile::fake()->image('poll_img2.jpg', 1)]
  ];
  $response = $this->actingAs($this->creator)->postJson('/api/post', $data);
  $response->assertStatus(200);
  $this->assertDatabaseCount('posts', 1);
  $this->assertDatabaseCount('polls', 2);
  $this->assertDatabaseCount('files', 2);
});

test('Posts can be paginated', function() {
  $posts = Post::factory(10)->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $response = $this->actingAs($this->creator)->getJson('/api/posts/channel?offset=1&limit=5');
  $response->assertStatus(200);
  $response->assertJsonCount(5, 'data');
});

test('User can see his all post', function() {
  $posts = Post::factory(10)->createQuietly([
    'channel_id' => $this->creator->id,
  ]);
  $response = $this->actingAs($this->creator)->getJson('/api/posts/channel');
  $response->assertStatus(200);
  $response->assertJsonCount($posts->count(), 'data');
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
  $posts = Post::factory(3)->createQuietly([
    'channel_id' => $this->creator->id,
    'visibility' => 'scheduled'
  ]);
  $response = $this->actingAs($this->admin)->getJson('/api/posts/channel/'. $this->creator->id);
  $response->assertStatus(200);
  $response->assertJsonCount($posts->count(), 'data');
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
        'reviewed',
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