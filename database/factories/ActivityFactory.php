<?php

namespace Database\Factories;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'category' => ActivityCategory::TalentDay,
            'slug' => Str::slug($title),
            'title' => $title,
            'description' => fake()->sentence(15),
            'icon' => fake()->randomElement(['code', 'globe', 'cpu', 'gamepad', 'cooking']),
            'image' => null,
            'schedule' => 'Setiap Jumat',
            'body' => [
                fake()->paragraph(),
                ['list' => [fake()->sentence(), fake()->sentence()]],
            ],
            'sort_order' => 0,
        ];
    }

    /**
     * An extracurricular club instead of a Talent Day class.
     */
    public function ekskul(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ActivityCategory::Ekskul,
            'icon' => fake()->randomElement(['ball', 'mosque', 'tent', 'trophy']),
        ]);
    }
}
