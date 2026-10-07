<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Foto unggahan (disk public). Tanpa middleware "web" (cookie terenkripsi & session): foto publik tidak butuh itu,
// dan kalau APP_KEY belum diisi middleware tersebut membuat setiap foto gagal dengan error 500
Route::get('storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/'.$path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]);
})->where('path', '.*')->withoutMiddleware('web');

Route::options('storage/{path}', function () {
    return response('', 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]);
})->where('path', '.*')->withoutMiddleware('web');
