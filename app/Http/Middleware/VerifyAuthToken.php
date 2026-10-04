<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyAuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Ekstraksi access_token dari Request Cookie atau Authorization Bearer
        $token = $this->extractToken($request);

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token otentikasi tidak ditemukan',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Forward access_token ke Auth Microservice dengan format JSON Payload
        $baseUrl = config('services.auth_service.base_url', 'http://user:80');
        $verifyUrl = rtrim($baseUrl, '/') . '/api/user/verify';

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($verifyUrl, [
                    'access_token' => $token,
                ]);
        } catch (\Throwable $e) {
            Log::error('Auth service connection failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Layanan otentikasi sedang tidak tersedia. Silakan coba beberapa saat lagi.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$response->successful() || !$response->json('success')) {
            $errorMessage = $response->json('message') ?? 'Token otentikasi tidak valid atau telah kedaluwarsa';
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userData = $response->json('data');

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengguna tidak ditemukan dalam respon otentikasi',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 3. Validasi status keaktifan akun
        if (isset($userData['status_aktif']) && $userData['status_aktif'] === false) {
            return response()->json([
                'success' => false,
                'message' => 'Akun pengguna sedang dinonaktifkan',
            ], Response::HTTP_FORBIDDEN);
        }

        // 4. Validasi Role (RBAC) jika parameter role disertakan pada route middleware
        if (!empty($roles)) {
            $userRole = strtoupper((string) ($userData['role'] ?? ''));
            $allowedRoles = array_map('strtoupper', $roles);

            if (!in_array($userRole, $allowedRoles, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: role ' . ($userData['role'] ?? 'UNKNOWN') . ' tidak memiliki izin untuk mengakses resource ini',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // 5. Merge metadata user ke dalam Request agar dapat diakses via $request->auth_user atau $request->input('auth_user')
        $request->merge([
            'auth_user' => $userData,
        ]);

        // Simpan juga ke request attributes & user resolver bawaan Laravel
        $request->attributes->set('auth_user', $userData);
        $request->setUserResolver(fn () => (object) $userData);

        return $next($request);
    }

    /**
     * Ekstraksi access_token dari:
     * - Authorization Bearer: Authorization: Bearer <token>
     * - Request Cookie: access_token
     */
    protected function extractToken(Request $request): ?string
    {
        // 1. Authorization: Bearer <token>
        $bearerToken = $request->bearerToken();
        if (!empty($bearerToken)) {
            return trim($bearerToken);
        }

        // 2. Request Cookie: access_token
        $cookieToken = $request->cookie('access_token') ?? $request->cookies->get('access_token');
        if (!empty($cookieToken) && is_string($cookieToken)) {
            return trim($cookieToken);
        }

        // Fallback Header Cookie mentah jika ada
        $rawCookie = $request->header('cookie');
        if (!empty($rawCookie) && is_string($rawCookie)) {
            if (preg_match('/(?:^|;\s*)access_token=([^;]+)/', $rawCookie, $matches)) {
                return trim(urldecode($matches[1]));
            }
        }

        return null;
    }
}
