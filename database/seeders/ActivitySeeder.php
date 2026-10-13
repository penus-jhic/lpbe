<?php

namespace Database\Seeders;

use App\Models\Activity;
use Database\Seeders\Concerns\ReadsSeedContent;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    use ReadsSeedContent;

    /**
     * Seeds the initial Talent Day classes and extracurricular clubs, each category in its homepage order.
     * Skipped when activities already exist.
     */
    public function run(): void
    {
        if (Activity::query()->exists()) {
            $this->command?->warn('Activities already exist, skipping.');

            return;
        }

        $positions = [];

        foreach ($this->readContent('activities') as $activity) {
            $category = $activity['category'];
            $positions[$category] = ($positions[$category] ?? -1) + 1;

            Activity::query()->create([
                ...$activity,
                'image' => $this->storeImage($activity['image'], 'activities'),
                'sort_order' => $positions[$category],
            ]);
        }
    }
}
