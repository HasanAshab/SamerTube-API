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
    public function definition()
    {
        return [
          'channel_id' => 1,
          'title' => fake()->unique()->text(10),
          'description' => 'bla bla bla bla',
          'visibility' => 'public',
          'category' => '1',
          'video' => 'video',
          'thumbnail' => 'thumbnail',
          'duration' => '5'
        ];
    }
}
