<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\ProgramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Public endpoints read by the landing page.
*/
Route::get('news', [NewsController::class, 'index'])->name('news.index');
Route::get('news/{slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
Route::get('programs/{slug}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');
Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
Route::get('activities/{slug}', [ActivityController::class, 'show'])->name('activities.show');

/*
| Admin panel endpoints, authenticated with user microservice token.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('verify.auth:ADMIN,KEPALA_SEKOLAH,TU')->group(function () {
        Route::get('me', function (Request $request) {
            return response()->json([
                'data' => $request->attributes->get('auth_user') ?? $request->user(),
            ]);
        })->name('me');

        Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

        Route::apiResource('news', Admin\NewsController::class);

        Route::post('programs/reorder', [Admin\ProgramController::class, 'reorder'])->name('programs.reorder');
        Route::apiResource('programs', Admin\ProgramController::class);

        Route::post('facilities/reorder', [Admin\FacilityController::class, 'reorder'])->name('facilities.reorder');
        Route::apiResource('facilities', Admin\FacilityController::class);

        Route::post('activities/reorder', [Admin\ActivityController::class, 'reorder'])->name('activities.reorder');
        Route::apiResource('activities', Admin\ActivityController::class);
    });
});
