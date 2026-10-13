<?php

use App\Enums\ActivityCategory;
use App\Models\Activity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A complete, valid admin activity form as the frontend sends it (body as a JSON string).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adminActivityForm(array $overrides = []): array
{
    return [
        'category' => 'talent_day',
        'title' => 'Tata Boga',
        'description' => 'Belajar memasak dari resep rumahan hingga menu yang layak dijual.',
        'icon' => 'cooking',
        'slug' => '',
        'schedule' => 'Setiap Jumat',
        'body' => json_encode(['Belajar memasak di dapur sekolah.', ['list' => ['Teknik dasar', 'Penyajian']]]),
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists only the requested category in order', function () {
        actingAsAdmin();
        $second = Activity::factory()->create(['sort_order' => 1]);
        $first = Activity::factory()->create(['sort_order' => 0]);
        Activity::factory()->ekskul()->create();

        $this->getJson('/api/admin/activities?category=talent_day')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$first->id, $second->id]);
    });
});

describe('store', function () {
    it('creates the activity at the end of its own category with its photo', function () {
        Storage::fake('public');
        actingAsAdmin();
        Activity::factory()->create(['sort_order' => 2]);
        Activity::factory()->ekskul()->create(['sort_order' => 9]);

        $response = $this->post('/api/admin/activities', adminActivityForm(['image' => UploadedFile::fake()->image('masak.jpg')]));

        $response->assertCreated()
            ->assertJsonPath('data.category', 'talent_day')
            ->assertJsonPath('data.slug', 'tata-boga')
            ->assertJsonPath('data.body', ['Belajar memasak di dapur sekolah.', ['list' => ['Teknik dasar', 'Penyajian']]])
            ->assertJsonPath('data.schedule', 'Setiap Jumat')
            ->assertJsonPath('data.sort_order', 3);

        Storage::disk('public')->assertExists(Activity::query()->findOrFail($response->json('data.id'))->image);
    });

    it('accepts an activity without a detail body', function () {
        actingAsAdmin();

        $this->post('/api/admin/activities', adminActivityForm(['body' => '[]']))
            ->assertCreated()
            ->assertJsonPath('data.body', []);
    });

    it('makes the generated slug unique', function () {
        actingAsAdmin();
        Activity::factory()->create(['slug' => 'tata-boga']);

        $this->post('/api/admin/activities', adminActivityForm())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'tata-boga-2');
    });

    it('starts a category that has no activities yet at the top', function () {
        actingAsAdmin();
        Activity::factory()->create(['sort_order' => 5]);

        $this->post('/api/admin/activities', adminActivityForm(['category' => 'ekskul', 'schedule' => '']))
            ->assertCreated()
            ->assertJsonPath('data.sort_order', 0)
            ->assertJsonPath('data.schedule', null);
    });

    it('reports every required field when the form is empty', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/activities', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'category' => 'Kategori wajib diisi.',
                'title' => 'Judul wajib diisi.',
                'description' => 'Deskripsi wajib diisi.',
                'icon' => 'Ikon wajib diisi.',
                'body' => 'Isi wajib ada.',
            ]);
    });

    it('rejects an unknown category', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/activities', adminActivityForm(['category' => 'kelas']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category' => 'Kategori yang dipilih tidak valid.']);
    });
});

describe('update', function () {
    it('saves the changes without moving the activity', function () {
        actingAsAdmin();
        $activity = Activity::factory()->create(['sort_order' => 3]);

        $this->put("/api/admin/activities/{$activity->id}", adminActivityForm(['title' => 'Tata Boga Lanjutan']))
            ->assertOk()
            ->assertJsonPath('data.title', 'Tata Boga Lanjutan')
            ->assertJsonPath('data.sort_order', 3);
    });

    it('moves an activity switched to the other category to the end of that category', function () {
        actingAsAdmin();
        Activity::factory()->ekskul()->create(['sort_order' => 4]);
        $activity = Activity::factory()->create(['sort_order' => 0]);

        $this->put("/api/admin/activities/{$activity->id}", adminActivityForm(['category' => 'ekskul']))
            ->assertOk()
            ->assertJsonPath('data.category', 'ekskul')
            ->assertJsonPath('data.sort_order', 5);
    });

    it('removes the photo file when the photo is cleared', function () {
        Storage::fake('public');
        actingAsAdmin();
        $image = UploadedFile::fake()->image('foto.jpg')->store('activities', 'public');
        $activity = Activity::factory()->create(['image' => $image]);

        $this->put("/api/admin/activities/{$activity->id}", adminActivityForm(['remove_image' => '1']))
            ->assertOk()
            ->assertJsonPath('data.image', null);

        Storage::disk('public')->assertMissing($image);
    });
});

describe('destroy', function () {
    it('deletes the activity and its photo file', function () {
        Storage::fake('public');
        actingAsAdmin();
        $image = UploadedFile::fake()->image('foto.jpg')->store('activities', 'public');
        $activity = Activity::factory()->create(['image' => $image]);

        $this->deleteJson("/api/admin/activities/{$activity->id}")->assertNoContent();

        $this->assertModelMissing($activity);
        Storage::disk('public')->assertMissing($image);
    });
});

describe('reorder', function () {
    it('saves the new order of one category and leaves the other untouched', function () {
        actingAsAdmin();
        [$first, $second] = Activity::factory()->count(2)->sequence(fn ($sequence) => ['sort_order' => $sequence->index])->create();
        $club = Activity::factory()->ekskul()->create(['sort_order' => 7]);

        $this->postJson('/api/admin/activities/reorder', ['category' => 'talent_day', 'ids' => [$second->id, $first->id]])
            ->assertOk()
            ->assertJsonPath('data.*.id', [$second->id, $first->id]);

        expect($club->refresh()->sort_order)->toBe(7);
    });

    it('rejects an activity from the other category', function () {
        actingAsAdmin();
        $activity = Activity::factory()->create();
        $club = Activity::factory()->ekskul()->create();

        $this->postJson('/api/admin/activities/reorder', ['category' => ActivityCategory::TalentDay->value, 'ids' => [$club->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0' => 'Urutan yang dipilih tidak valid.']);

        expect($activity->refresh()->sort_order)->toBe(0);
    });
});

it('requires an administrator', function () {
    $this->getJson('/api/admin/activities')->assertUnauthorized();
});
