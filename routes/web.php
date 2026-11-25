<?php

use App\Http\Controllers\Admin\AboutPageSettingController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BannerImageController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactPageSettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\MenuController;
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
    Route::resource('banners', BannerController::class)->except('show');
    Route::patch('/banners/{banner}/status', [BannerController::class, 'toggleStatus'])->name('banners.status');
    Route::post('/banners/{banner}/images', [BannerImageController::class, 'store'])->name('banners.images.store');
    Route::patch('/banners/{banner}/images/{bannerImage}', [BannerImageController::class, 'update'])->name('banners.images.update');
    Route::delete('/banners/{banner}/images/{bannerImage}', [BannerImageController::class, 'destroy'])->name('banners.images.destroy');
    Route::patch('/banners/{banner}/images/{bannerImage}/main', [BannerImageController::class, 'makeMain'])->name('banners.images.main');
    Route::resource('subcategories', SubcategoryController::class)->except('show');
    Route::resource('menus', MenuController::class)->except('show');
    Route::patch('/menus/{menu}/status', [MenuController::class, 'toggleStatus'])->name('menus.status');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('products', ProductController::class);
    Route::get('/inventory', [ProductController::class, 'index'])->defaults('inventory', true)->name('inventory');
    Route::get('/media', MediaLibraryController::class)->name('media.index');
    Route::get('/settings', [ProfileController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [ProfileController::class, 'update'])->name('settings.update');
    Route::get('/contact-page-settings', [ContactPageSettingController::class, 'edit'])->name('contact-page-settings.edit');
    Route::put('/contact-page-settings', [ContactPageSettingController::class, 'update'])->name('contact-page-settings.update');
    Route::get('/about-page-settings', [AboutPageSettingController::class, 'edit'])->name('about-page-settings.edit');
    Route::put('/about-page-settings', [AboutPageSettingController::class, 'update'])->name('about-page-settings.update');
});
