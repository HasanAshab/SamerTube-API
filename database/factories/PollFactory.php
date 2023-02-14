<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
* @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Poll>
*/
class PollFactory extends Factory
{
  /**
  * Define the model's default state.
  *
  * @return array<string, mixed>
  */
  public function definition() {
    $posts_id = \App\Models\Post::pluck('id')->toArray();
    return [
      'post_id' => fake()->randomElement($posts_id),
      'name' => fake()->name(),
      //'vote_count' => random_int(0, 20),
    ];
  }
}