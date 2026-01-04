<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

// Login endpoint
Route::post('/login', [LoginController::class, 'login']);

// Logout endpoint (requires authentication)
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth.token');

// Verify token endpoint
Route::get('/verify-token', [LoginController::class, 'verifyToken'])
    ->middleware('auth.token');

// Get current user info
Route::get('/user', function (Request $request) {
    return response()->json([
        'user_id' => $request->user_id,
        'user_type' => $request->user_type
    ]);
})->middleware('auth.token');

use App\Http\Controllers\EnrollController;

Route::post('/enroll', [EnrollController::class, 'enroll']);

use App\Http\Controllers\StudentRegistrationController;

Route::post('/student-register', [StudentRegistrationController::class, 'register']);

use App\Http\Controllers\StaffRegistrationController;

Route::post('/staff-register', [StaffRegistrationController::class, 'register']);

use App\Http\Controllers\NoticeController;

Route::post('/notices', [NoticeController::class, 'store'])->middleware('token.auth');

use App\Http\Controllers\ChangePasswordController;

Route::post('/change-password', [ChangePasswordController::class, 'update'])->middleware('token.auth');

use App\Http\Controllers\StaffHomeController;

Route::middleware('token.auth')->group(function () {
    Route::get('/staff/home/notices', [StaffHomeController::class, 'getNotices']);
    Route::get('/departments-batches', [StaffHomeController::class, 'getDepartmentsBatches']);
    Route::post('/staff/home/notice/delete', [StaffHomeController::class, 'deleteNotice']);
    Route::post('/staff/home/notice/update', [StaffHomeController::class, 'updateNotice']);
});

use App\Http\Controllers\StaffProfileController;

Route::middleware('token.auth')->group(function () {
    Route::get('/staff/profile', [StaffProfileController::class, 'getProfile']);
    Route::post('/staff/profile/picture', [StaffProfileController::class, 'updateProfilePicture']);
});


use App\Http\Controllers\StudentHomeController;

Route::middleware(['token.auth'])->group(function () {
    Route::get('/student/home/profile', [StudentHomeController::class, 'getProfile']);
    Route::get('/student/notices', [StudentHomeController::class, 'getNotices']);
    Route::post('/student/home/profile/picture', [StudentHomeController::class, 'updateProfilePicture']);
});

use App\Http\Controllers\StudentProfileController;

Route::middleware(['token.auth'])->group(function() {
    Route::get('/student/profile', [StudentProfileController::class, 'getProfile']);
    Route::post('/student/profile/picture', [StudentProfileController::class, 'updateProfilePicture']);
});

