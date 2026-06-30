<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ItemproductionController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\CameraConfigController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;

Route::redirect('/', '/login');

Route::get('pdf', [PDFController::class, 'index']);

// Admin + Benutzer — Schreibzugriff
// Muss vor den read-only Resource-Routes stehen, damit z. B. "units/create"
// nicht von "units/{unit}" (show) abgefangen wird.
Route::middleware(['auth', 'role:admin,user'])->group(function () {
    Route::resource('/units', UnitController::class)->except(['index', 'show']);
    Route::resource('/items', ItemController::class)->except(['index', 'show']);
    Route::resource('/suppliers', SupplierController::class)->except(['index', 'show']);

    Route::get('productions/templates-search', [ProductionController::class, 'searchTemplates'])->name('productions.searchTemplates');
    Route::resource('/productions', ProductionController::class)->except(['index', 'show']);
    Route::post('productions/{id}/attach-item', [ProductionController::class, 'attachItem'])->name('productions.attachItem');
    Route::delete('productions/{id}/detach-item/{itemId}', [ProductionController::class, 'detachItem'])->name('productions.detachItem');
    Route::get('productions/{production}/import-from/{source}', [ProductionController::class, 'importFrom'])->name('productions.importFrom');
    Route::post('productions/{production}/import-from/{source}', [ProductionController::class, 'storeImport'])->name('productions.storeImport');

    Route::get('/camera-configs/{config}/edit', [CameraConfigController::class, 'edit'])->name('camera-config.edit');
    Route::put('/camera-configs/{config}', [CameraConfigController::class, 'update'])->name('camera-config.update');
    Route::delete('/camera-configs/{config}', [CameraConfigController::class, 'destroy'])->name('camera-config.destroy');
    Route::get('/productions/{production}/camera-config/create', [ProductionController::class, 'createCameraConfig'])->name('camera-config.create');
    Route::post('/productions/{production}/camera-config', [ProductionController::class, 'storeCameraConfig'])->name('camera-config.store');
});

// Alle eingeloggten User (inkl. Viewer) — nur lesend
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('/units', UnitController::class)->only(['index', 'show']);
    Route::resource('/items', ItemController::class)->only(['index', 'show']);
    Route::resource('/productions', ProductionController::class)->only(['index', 'show']);
    Route::resource('/suppliers', SupplierController::class)->only(['index', 'show']);

    Route::get('/itemprods', [ItemproductionController::class, 'index'])->name('itemprods');
    Route::get('productions/{id}/pdf', [ProductionController::class, 'generatePDF'])->name('productions.pdf');
    Route::get('productions/{id}/requirements', [ProductionController::class, 'requirements'])->name('productions.requirements');

    Route::get('/timeline/items', [TimelineController::class, 'items'])->name('timeline.items');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin only — Benutzerverwaltung
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('/users', UserController::class);
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
});

require __DIR__.'/auth.php';
