<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// NAVER API Testing Page
Route::get('/naver-test', [App\Http\Controllers\NaverTestController::class, 'index'])->name('naver.test');
Route::post('/api/test/maps', [App\Http\Controllers\NaverTestController::class, 'testMaps']);
Route::post('/api/test/papago', [App\Http\Controllers\NaverTestController::class, 'testPapago']);
Route::post('/api/test/papago-image', [App\Http\Controllers\NaverTestController::class, 'testPapagoImage']);
Route::post('/api/test/greeneye', [App\Http\Controllers\NaverTestController::class, 'testGreenEye']);
Route::post('/api/test/ocr', [App\Http\Controllers\NaverTestController::class, 'testOcr']);
Route::post('/api/test/speech', [App\Http\Controllers\NaverTestController::class, 'testSpeech']);

require __DIR__.'/settings.php';
