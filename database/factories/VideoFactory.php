<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
* @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\odel=Video>
*/
class VideoFactory extends Factory
{
  /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
  public function definition() {
    $channels_id = \App\Models\Channel::pluck('id')->toArray();
    $categories_id = \App\Models\Category::pluck('id')->toArray();
    return [
      'channel_id' => fake()->randomElement($channels_id),
      'title' => fake()->unique()->text(10),
      'description' => 'bla bla bla bla',
      'visibility' => 'public',
      'category_id' => fake()->randomElement($categories_id),
      'video_url' => 'https//www.example.com',
      'thumbnail_url' => 'https//www.example.com',
      'duration' => '5'
    ];
  }
}