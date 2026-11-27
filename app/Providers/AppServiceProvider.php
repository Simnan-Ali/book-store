<?php

namespace App\Providers;

use App\Models\AdminBrandingSetting;
use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);

        Gate::define('admin-branding.manage', static fn (User $user): bool => $user->isSuperAdmin() || $user->hasActiveRole('admin'));

        View::composer(['layouts.admin', 'auth.login'], static function ($view): void {
            $view->with('adminBranding', AdminBrandingSetting::singleton());
        });

        Gate::before(static function ($user, string $ability): ?bool {
            return $user instanceof User && $user->isSuperAdmin() ? true : null;
        });
    }
}
