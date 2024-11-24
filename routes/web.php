<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ItemproductionController;


Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/units', UnitController::class);
});   

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/items', ItemController::class);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/productions', ProductionController::class);
    Route::post('productions/{id}/attach-item', [ProductionController::class, 'attachItem'])->name('productions.attachItem');
    Route::delete('productions/{id}/detach-item/{itemId}', [ProductionController::class, 'detachItem'])->name('productions.detachItem');
});



//Route::get('/productions', [ProductionController::class, 'index'])->middleware(['auth', 'verified'])->name('productions');
//Route::get('productions/{id}', [ProductionController::class, 'show'])->name('productions.show');

Route::get('/suppliers', [SupplierController::class,
'index'])->middleware(['auth', 'verified'])->name('suppliers');

Route::get('/bookings', [BookingController::class,
'index'])->middleware(['auth', 'verified'])->name('bookings');

Route::get('/itemprods', [ItemproductionController::class,
'index'])->middleware(['auth', 'verified'])->name('itemprods');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
