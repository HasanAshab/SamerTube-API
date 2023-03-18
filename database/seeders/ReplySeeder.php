<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Channel;
use App\Models\Reply;
use App\Models\Comment;

class ReplySeeder extends Seeder
{
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run() {
    $user = User::factory()->create();
    $channel = Channel::factory()->create();
    $comment = Comment::first();
    Reply::factory(500)->createQuietly([
      'replier_id' => $user->id,
      'comment_id' => $comment->id,
    ]);
  }
}