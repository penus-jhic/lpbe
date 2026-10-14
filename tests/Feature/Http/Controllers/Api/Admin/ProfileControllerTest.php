<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(fn () => $this->markTestSkipped('Profil admin dikelola microservice user (/api/user/profile), route /api/admin/profile sudah dihapus.'));

it('updates the name and email', function () {
    $admin = actingAsAdmin();

    $this->putJson('/api/admin/profile', ['name' => 'Humas Sekolah', 'email' => 'humas@pelitanusantara.sch.id'])
        ->assertOk()
        ->assertJsonPath('data.email', 'humas@pelitanusantara.sch.id');

    expect($admin->refresh()->name)->toBe('Humas Sekolah');
});

it('rejects an email used by another admin', function () {
    actingAsAdmin();
    $other = User::factory()->create();

    $this->putJson('/api/admin/profile', ['name' => 'Admin', 'email' => $other->email])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Email sudah dipakai.']);
});

it('changes the password and signs out other devices but not this one', function () {
    $admin = User::factory()->create(['password' => 'kata-sandi-lama']);
    $currentToken = $admin->createToken('admin-panel');
    $admin->createToken('admin-panel');

    $this->withToken($currentToken->plainTextToken)->putJson('/api/admin/profile', [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'kata-sandi-lama',
        'password' => 'kata-sandi-baru',
        'password_confirmation' => 'kata-sandi-baru',
    ])->assertOk();

    expect(Hash::check('kata-sandi-baru', $admin->refresh()->password))->toBeTrue()
        ->and(PersonalAccessToken::query()->pluck('id')->all())->toBe([$currentToken->accessToken->id]);
});

it('rejects a password change with the wrong current password', function () {
    $admin = User::factory()->create(['password' => 'kata-sandi-lama']);
    $token = $admin->createToken('admin-panel');

    $this->withToken($token->plainTextToken)->putJson('/api/admin/profile', [
        'name' => $admin->name,
        'email' => $admin->email,
        'current_password' => 'salah',
        'password' => 'kata-sandi-baru',
        'password_confirmation' => 'kata-sandi-baru',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password' => 'Kata sandi saat ini salah.']);

    expect(Hash::check('kata-sandi-lama', $admin->refresh()->password))->toBeTrue();
});

it('requires the current password to set a new one', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/profile', [
        'name' => 'Admin',
        'email' => 'admin@pelitanusantara.sch.id',
        'password' => 'kata-sandi-baru',
        'password_confirmation' => 'kata-sandi-baru',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password' => 'Kata sandi saat ini wajib diisi bila kata sandi diisi.']);
});
