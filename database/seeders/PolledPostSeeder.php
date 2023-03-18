<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Channel;
use App\Models\Post;
use App\Models\Poll;

class PolledPostSeeder extends Seeder
{
  public function run() {
    $channel = Channel::first();
    $posts = Post::factory(200)->createQuietly([
      'channel_id' => $channel->id,
      'type' => 'text_poll',
      'total_votes' => 0
    ]);
    $posts->each(function($post){
      Poll::factory(5)->create([
        'post_id' => $post->id
      ]);
    });
  }
}