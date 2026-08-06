<?php

namespace App\Providers;

use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AdminProductService;
use App\Services\Admin\UserService;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\CheckoutService;
use App\Services\CommissionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ProductImageService;
use App\Services\ProductReviewService;
use App\Services\ProductService;
use App\Services\ProductVariationService;
use App\Services\ProfileService;
use App\Services\TransactionService;
use App\Services\VendorDashboardService;
use App\Services\VendorPayoutService;
use App\Services\VendorProfileService;
use App\Services\VendorService;
use App\Services\WishlistService;
use Illuminate\Support\ServiceProvider;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind services as singletons and allow Laravel to resolve constructor dependencies.
        // (e.g. ProductService requires other services in its constructor)
        $this->app->singleton(AuthService::class);
        $this->app->singleton(VendorService::class);
        $this->app->singleton(CategoryService::class);

        // Explicitly bind Product dependencies too (optional, but keeps intent clear)
        $this->app->singleton(ProductImageService::class);
        $this->app->singleton(ProductVariationService::class);
        $this->app->singleton(ProductReviewService::class);
        $this->app->singleton(ProductService::class);
        $this->app->singleton (OrderService::class);
        $this->app->singleton (CartService::class);
        $this->app->singleton (CheckoutService::class);
        $this->app->singleton (ProfileService::class);
        $this->app->singleton (WishlistService::class);
        $this->app->singleton (VendorDashboardService::class);
        $this->app->singleton (VendorProfileService::class);
         $this->app->singleton(PaymentService::class);
        $this->app->singleton(VendorPayoutService::class);
        $this->app->singleton(TransactionService::class);
        $this->app->singleton(CommissionService::class);
        $this->app->singleton(AdminDashboardService::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(AdminProductService::class);



    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
