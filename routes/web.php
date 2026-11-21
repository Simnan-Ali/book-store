<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('subcategories', SubcategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::get('/inventory', [ProductController::class, 'index'])->defaults('inventory', true)->name('inventory');
    Route::get('/media', MediaLibraryController::class)->name('media.index');
    Route::get('/settings', [ProfileController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [ProfileController::class, 'update'])->name('settings.update');
});
