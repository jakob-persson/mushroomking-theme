<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdventureController;
use App\Http\Controllers\RegisterFlowController;

/*
|--------------------------------------------------------------------------
| Home / Landing
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Auth routes (Breeze/Fortify)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})
->middleware(['auth', 'verified'])
->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile routes (auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use App\Http\Controllers\ProfileEditController;

Route::middleware('auth')->group(function () {
    Route::post('/profile-modal/update', [ProfileEditController::class, 'update'])->name('profileModal.update');
    Route::post('/profile-modal/avatar', [ProfileEditController::class, 'avatar'])->name('profileModal.avatar');
});


/*
|--------------------------------------------------------------------------
| Public profile page
|--------------------------------------------------------------------------
*/


Route::middleware('auth')->group(function () {
    Route::post('/profile-modal/update', [\App\Http\Controllers\ProfileController::class, 'updateModal'])
        ->name('profileModal.update');

    Route::post('/profile-modal/avatar', [\App\Http\Controllers\ProfileController::class, 'uploadAvatar'])
        ->name('profileModal.avatar');
});

Route::get('/u/{user:slug}', [ProfileController::class, 'show'])->name('profile.show');

Route::post('/profile-modal/avatar', [\App\Http\Controllers\ProfileController::class, 'uploadAvatar'])
    ->middleware('auth')
    ->name('profileModal.avatar');


/*
|--------------------------------------------------------------------------
| Feed (auth)
|--------------------------------------------------------------------------
*/

Route::get('/feed', [FeedController::class, 'index'])
    ->middleware('auth')
    ->name('feed.index');

/*
|--------------------------------------------------------------------------
| Adventures (auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::post('/adventures', [AdventureController::class, 'store'])->name('adventures.store');
    Route::put('/adventures/{adventure}', [AdventureController::class, 'update'])->name('adventures.update'); // ✅ NY
    Route::delete('/adventures/{adventure}', [AdventureController::class, 'destroy'])->name('adventures.destroy');
});

/*
|--------------------------------------------------------------------------
| Register flow (guest)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/create-account', [RegisterFlowController::class, 'show'])
        ->name('register.flow.show');

    Route::post('/create-account/send-code', [RegisterFlowController::class, 'sendCode'])
        ->name('register.flow.send_code');

    Route::post('/create-account/resend-code', [RegisterFlowController::class, 'resendCode'])
        ->name('register.flow.resend_code');

    Route::post('/create-account/verify-code', [RegisterFlowController::class, 'verifyCode'])
        ->name('register.flow.verify_code');

    Route::post('/create-account/password', [RegisterFlowController::class, 'savePassword'])
        ->name('register.flow.password');

    Route::post('/create-account/finish', [RegisterFlowController::class, 'finish'])
        ->name('register.flow.finish');
});


/*
|--------------------------------------------------------------------------
| Dev routes (auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dev/test-adventure', fn () => view('dev.test-adventure'));
    Route::get('/dev/modal', fn () => view('dev.modal'));
});
