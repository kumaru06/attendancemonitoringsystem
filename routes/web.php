<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceExportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\Sf2ExportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\StudentQrController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', function () {
        return redirect()->route(auth()->user()->homeRoute());
    })->name('home');

    Route::middleware('role:admin,scanner')->group(function () {
        Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner.index');
        Route::post('/scanner/scan', [ScannerController::class, 'scan'])
            ->middleware('throttle:scan')
            ->name('scanner.scan');

        Route::get('/students/{student}/photo', StudentPhotoController::class)->name('students.photo');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('students', StudentController::class)->except(['destroy']);
        Route::get('/students/{student}/qr', [StudentQrController::class, 'show'])->name('students.qr');
        Route::get('/students/{student}/qr/download', [StudentQrController::class, 'download'])->name('students.qr.download');
        Route::post('/students/{student}/qr/replace', [StudentQrController::class, 'replace'])->name('students.qr.replace');

        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');

        Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendances/export', AttendanceExportController::class)->name('attendances.export');
        Route::get('/attendances/sf2', Sf2ExportController::class)->name('attendances.sf2');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
