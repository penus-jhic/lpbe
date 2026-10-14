<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

describe('login', function () {
    beforeEach(fn () => $this->markTestSkipped('Login admin pindah ke microservice user (/api/user/login), route /api/admin/login sudah dihapus.'));

    it('returns an API token that authenticates later requests', function () {
        $admin = User::factory()->create(['password' => 'rahasia-sekolah']);

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'rahasia-sekolah',
        ]);

        $response->assertOk()->assertJsonPath('user.email', $admin->email);

        $this->withToken($response->json('token'))
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id);
    });

    it('issues tokens that expire after seven days', function () {
        $admin = User::factory()->create(['password' => 'rahasia-sekolah']);

        $this->freezeSecond();

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'rahasia-sekolah'])->assertOk();

        expect(PersonalAccessToken::query()->sole()->expires_at->equalTo(now()->addDays(7)))->toBeTrue();
    });

    it('returns 422 with a message for a wrong password', function () {
        $admin = User::factory()->create();

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'salah'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Email atau kata sandi salah.']);

        expect(PersonalAccessToken::query()->count())->toBe(0);
    });

    it('returns 429 after five failed attempts within a minute', function () {
        $admin = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'salah'])->assertUnprocessable();
        }

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'salah'])->assertTooManyRequests();
    });
});

describe('logout', function () {
    beforeEach(fn () => $this->markTestSkipped('Logout admin pindah ke microservice user, route /api/admin/logout sudah dihapus.'));

    it('revokes only the token used for the request', function () {
        $admin = User::factory()->create();
        $currentToken = $admin->createToken('admin-panel');
        $otherDeviceToken = $admin->createToken('admin-panel');

        $this->withToken($currentToken->plainTextToken)->postJson('/api/admin/logout')->assertNoContent();

        expect(PersonalAccessToken::query()->pluck('id')->all())->toBe([$otherDeviceToken->accessToken->id]);
    });
});

it('returns 401 from every admin endpoint without a token', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with([
    'me' => ['GET', '/api/admin/me'],
    'dashboard' => ['GET', '/api/admin/dashboard'],
    'news index' => ['GET', '/api/admin/news'],
    'news store' => ['POST', '/api/admin/news'],
    'news show' => ['GET', '/api/admin/news/1'],
    'news update' => ['PUT', '/api/admin/news/1'],
    'news destroy' => ['DELETE', '/api/admin/news/1'],
    'programs index' => ['GET', '/api/admin/programs'],
    'programs store' => ['POST', '/api/admin/programs'],
    'programs reorder' => ['POST', '/api/admin/programs/reorder'],
    'programs update' => ['PUT', '/api/admin/programs/1'],
    'programs destroy' => ['DELETE', '/api/admin/programs/1'],
    'facilities index' => ['GET', '/api/admin/facilities'],
    'facilities store' => ['POST', '/api/admin/facilities'],
    'facilities reorder' => ['POST', '/api/admin/facilities/reorder'],
    'facilities update' => ['PUT', '/api/admin/facilities/1'],
    'facilities destroy' => ['DELETE', '/api/admin/facilities/1'],
]);

it('returns 401 for an expired token', function () {
    Http::fake([
        '*/api/user/verify' => Http::response(['success' => false, 'message' => 'Token kedaluwarsa'], 401),
    ]);

    $this->withToken('expired-token')->getJson('/api/admin/me')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Token kedaluwarsa');
});

it('returns the account verified by the user microservice', function () {
    $admin = actingAsAdmin();

    $this->getJson('/api/admin/me')
        ->assertOk()
        ->assertJsonPath('data.id', $admin->id)
        ->assertJsonPath('data.role', 'ADMIN');

    Http::assertSent(fn ($request) => $request['access_token'] === 'admin-test-token');
});

it('returns 403 for a role without access to the admin panel', function () {
    Http::fake([
        '*/api/user/verify' => Http::response([
            'success' => true,
            'data' => ['id' => 'usr-siswa-001', 'role' => 'SISWA', 'status_aktif' => true],
        ]),
    ]);

    $this->withToken('siswa-token')->getJson('/api/admin/dashboard')->assertForbidden();
});

it('returns 503 when the user microservice is unreachable', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    $this->withToken('any-token')->getJson('/api/admin/me')->assertServiceUnavailable();
});
