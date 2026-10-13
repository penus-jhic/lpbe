<?php

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\Facility;
use App\Models\FacilityImage;
use App\Models\News;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds the admin and the original landing page content with photos', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', config('admin.email'))->exists())->toBeTrue()
        ->and(News::query()->published()->count())->toBe(14)
        ->and(Program::query()->ordered()->pluck('slug')->all())->toBe(['preneurs-day', 'devacto', 'bimbingan-karir', 'workshop-industri'])
        ->and(Facility::query()->count())->toBe(20)
        ->and(FacilityImage::query()->count())->toBe(30)
        ->and(Activity::query()->where('category', ActivityCategory::TalentDay)->ordered()->first()->title)->toBe('Tata Boga')
        ->and(Activity::query()->where('category', ActivityCategory::Ekskul)->count())->toBe(6);

    Storage::disk('public')->assertExists(News::query()->first()->image);
    Storage::disk('public')->assertExists(FacilityImage::query()->first()->path);
    Program::query()->each(fn (Program $program) => Storage::disk('public')->assertExists($program->image));
});

it('does not duplicate content when seeding again', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1)
        ->and(News::query()->count())->toBe(14)
        ->and(Program::query()->count())->toBe(4)
        ->and(Facility::query()->count())->toBe(20)
        ->and(Activity::query()->count())->toBe(11);
});
