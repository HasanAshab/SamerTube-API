<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
* @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
*/
class PostFactory extends Factory
{
  /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
  public function definition() {
    $channels_id = \App\Models\Channel::pluck('id')->toArray();
    return [
      'channel_id' => fake()->randomElement($channels_id),
      'content' => 'Hello this is a test post',
      'type' => 'text',
      'visibility' => fake()->randomElement(['public', 'scheduled']),
    ];
  }
}