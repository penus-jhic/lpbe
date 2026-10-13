<?php

use App\Models\Activity;

describe('index', function () {
    it('lists every activity in order with its category, without the body', function () {
        $second = Activity::factory()->create(['sort_order' => 1]);
        $first = Activity::factory()->create(['sort_order' => 0]);
        $club = Activity::factory()->ekskul()->create(['sort_order' => 0]);

        $this->getJson('/api/activities')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$first->id, $club->id, $second->id])
            ->assertJsonPath('data.0.category', 'talent_day')
            ->assertJsonPath('data.0.slug', $first->slug)
            ->assertJsonPath('data.1.category', 'ekskul')
            ->assertJsonMissingPath('data.0.body');
    });
});

describe('show', function () {
    it('returns the body with the next activities of the same category, wrapping around to the first', function () {
        $classes = Activity::factory()->count(5)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
        Activity::factory()->ekskul()->create(['sort_order' => 0]);

        $this->getJson("/api/activities/{$classes[3]->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $classes[3]->slug)
            ->assertJsonPath('data.body', $classes[3]->body)
            ->assertJsonPath('others.*.slug', [$classes[4]->slug, $classes[0]->slug, $classes[1]->slug]);
    });

    it('returns an empty body for an activity without one', function () {
        $activity = Activity::factory()->create(['body' => null]);

        $this->getJson("/api/activities/{$activity->slug}")
            ->assertOk()
            ->assertJsonPath('data.body', [])
            ->assertJsonPath('others', []);
    });

    it('returns 404 for an unknown activity', function () {
        Activity::factory()->create();

        $this->getJson('/api/activities/tidak-ada')->assertNotFound();
    });
});
