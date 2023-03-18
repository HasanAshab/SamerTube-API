<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Channel;
use App\Models\Post;
use App\Models\Comment;

class CommentSeeder extends Seeder
{
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run() {
    $user = User::factory()->create();
    $channel = Channel::factory()->create();
    $post = Post::factory()->createQuietly(['visibility' => 'public']);
    Comment::factory(500)->createQuietly([
      'commenter_id' => $user->id,
      'commentable_type' => Post::class,
      'commentable_id' => $post->id,
    ]);
  }
}