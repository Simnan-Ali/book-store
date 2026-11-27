<?php

use App\Http\Controllers\Admin\AboutPageSettingController;
use App\Http\Controllers\Admin\AdminBrandingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BannerImageController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactPageSettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FooterManagementController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

    Route::resource('categories', CategoryController::class)->except('show')
        ->middlewareFor('index', 'can:categories.view')
        ->middlewareFor(['create', 'store'], 'can:categories.create')
        ->middlewareFor(['edit', 'update'], 'can:categories.edit')
        ->middlewareFor('destroy', 'can:categories.delete');

    Route::resource('banners', BannerController::class)->except('show')
        ->middlewareFor('index', 'can:banners.view')
        ->middlewareFor(['create', 'store'], 'can:banners.create')
        ->middlewareFor(['edit', 'update'], 'can:banners.edit')
        ->middlewareFor('destroy', 'can:banners.delete');
    Route::patch('/banners/{banner}/status', [BannerController::class, 'toggleStatus'])->middleware('can:banners.edit')->name('banners.status');
    Route::post('/banners/{banner}/images', [BannerImageController::class, 'store'])->middleware('can:banners.edit')->name('banners.images.store');
    Route::patch('/banners/{banner}/images/{bannerImage}', [BannerImageController::class, 'update'])->middleware('can:banners.edit')->name('banners.images.update');
    Route::delete('/banners/{banner}/images/{bannerImage}', [BannerImageController::class, 'destroy'])->middleware('can:banners.edit')->name('banners.images.destroy');
    Route::patch('/banners/{banner}/images/{bannerImage}/main', [BannerImageController::class, 'makeMain'])->middleware('can:banners.edit')->name('banners.images.main');

    Route::resource('subcategories', SubcategoryController::class)->except('show')
        ->middlewareFor('index', 'can:subcategories.view')
        ->middlewareFor(['create', 'store'], 'can:subcategories.create')
        ->middlewareFor(['edit', 'update'], 'can:subcategories.edit')
        ->middlewareFor('destroy', 'can:subcategories.delete');

    Route::resource('menus', MenuController::class)->except('show')
        ->middlewareFor('index', 'can:menu-master.view')
        ->middlewareFor(['create', 'store'], 'can:menu-master.create')
        ->middlewareFor(['edit', 'update'], 'can:menu-master.edit')
        ->middlewareFor('destroy', 'can:menu-master.delete');
    Route::patch('/menus/{menu}/status', [MenuController::class, 'toggleStatus'])->middleware('can:menu-master.edit')->name('menus.status');

    Route::resource('brands', BrandController::class)->except('show')
        ->middlewareFor('index', 'can:brands.view')
        ->middlewareFor(['create', 'store'], 'can:brands.create')
        ->middlewareFor(['edit', 'update'], 'can:brands.edit')
        ->middlewareFor('destroy', 'can:brands.delete');

    Route::resource('products', ProductController::class)
        ->middlewareFor(['index', 'show'], 'can:books.view')
        ->middlewareFor(['create', 'store'], 'can:books.create')
        ->middlewareFor(['edit', 'update'], 'can:books.edit')
        ->middlewareFor('destroy', 'can:books.delete');
    Route::get('/inventory', [ProductController::class, 'index'])->defaults('inventory', true)->middleware('can:inventory.view')->name('inventory');
    Route::patch('/inventory/{product}/stock', [ProductController::class, 'updateInventory'])->middleware('can:inventory.update')->name('inventory.stock.update');
    Route::get('/media', MediaLibraryController::class)->middleware('can:media.view')->name('media.index');

    Route::get('/settings', [ProfileController::class, 'edit'])->middleware('can:profile.view')->name('settings.edit');
    Route::put('/settings', [ProfileController::class, 'update'])->middleware('can:profile.edit')->name('settings.update');
    Route::get('/admin-branding', [AdminBrandingController::class, 'edit'])->middleware('can:admin-branding.manage')->name('branding.edit');
    Route::put('/admin-branding', [AdminBrandingController::class, 'update'])->middleware('can:admin-branding.manage')->name('branding.update');
    Route::get('/contact-page-settings', [ContactPageSettingController::class, 'edit'])->middleware('can:contact-page.view')->name('contact-page-settings.edit');
    Route::put('/contact-page-settings', [ContactPageSettingController::class, 'update'])->middleware('can:contact-page.edit')->name('contact-page-settings.update');
    Route::get('/about-page-settings', [AboutPageSettingController::class, 'edit'])->middleware('can:about-page.view')->name('about-page-settings.edit');
    Route::put('/about-page-settings', [AboutPageSettingController::class, 'update'])->middleware('can:about-page.edit')->name('about-page-settings.update');

    Route::get('/footer', [FooterManagementController::class, 'edit'])->middleware('can:footer.view')->name('footer.edit');
    Route::put('/footer', [FooterManagementController::class, 'update'])->middleware('can:footer.edit')->name('footer.update');
    Route::post('/footer/sections', [FooterManagementController::class, 'storeSection'])->middleware('can:footer.sections.create')->name('footer.sections.store');
    Route::put('/footer/sections/{section}', [FooterManagementController::class, 'updateSection'])->middleware('can:footer.sections.edit')->name('footer.sections.update');
    Route::delete('/footer/sections/{section}', [FooterManagementController::class, 'destroySection'])->middleware('can:footer.sections.delete')->name('footer.sections.destroy');
    Route::post('/footer/sections/{section}/links', [FooterManagementController::class, 'storeLink'])->middleware('can:footer.links.create')->name('footer.links.store');
    Route::put('/footer/sections/{section}/links/{link}', [FooterManagementController::class, 'updateLink'])->middleware('can:footer.links.edit')->name('footer.links.update');
    Route::delete('/footer/sections/{section}/links/{link}', [FooterManagementController::class, 'destroyLink'])->middleware('can:footer.links.delete')->name('footer.links.destroy');
    Route::put('/footer/social', [FooterManagementController::class, 'updateSocial'])->middleware('can:footer.social-links.manage')->name('footer.social.update');

    Route::resource('roles', RoleController::class)->except('show')
        ->middlewareFor('index', 'can:roles.view')
        ->middlewareFor(['create', 'store'], 'can:roles.create')
        ->middlewareFor(['edit', 'update'], 'can:roles.edit')
        ->middlewareFor('destroy', 'can:roles.delete');
    Route::resource('permissions', PermissionController::class)->except('show')
        ->middlewareFor('index', 'can:permissions.view')
        ->middlewareFor(['create', 'store'], 'can:permissions.create')
        ->middlewareFor(['edit', 'update'], 'can:permissions.edit')
        ->middlewareFor('destroy', 'can:permissions.delete');
    Route::resource('users', AdminUserController::class)->except('show')
        ->middlewareFor('index', 'can:users.view')
        ->middlewareFor(['create', 'store'], 'can:users.create')
        ->middlewareFor(['edit', 'update'], 'can:users.edit')
        ->middlewareFor('destroy', 'can:users.delete');
});
