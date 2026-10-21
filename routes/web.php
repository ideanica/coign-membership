<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\CatalogImageController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationCatalogController;
use App\Http\Controllers\MemberAuthController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MembershipProfileController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\TestimonialController;
use App\Http\Middleware\EnsureActiveMember;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsurePasswordSetup;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalog', [InvitationCatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{catalog:slug}', [InvitationCatalogController::class, 'show'])->name('catalog.show');
Route::post('/catalog/{catalog:slug}/testimonials', [TestimonialController::class, 'store'])->middleware('throttle:5,1')->name('catalog.testimonials.store');
Route::get('/catalog-images/{image}', [CatalogImageController::class, 'show'])->name('catalog.images.show');
Route::get('/track', [PublicTrackingController::class, 'index'])->name('public.tracking.index');
Route::post('/track', [PublicTrackingController::class, 'lookup'])->middleware('throttle:20,1')->name('public.tracking.lookup');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [MemberAuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [MemberAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('throttle:20,1')->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:20,1')->name('auth.google.callback');
});

Route::middleware(['auth', EnsureActiveMember::class])->group(function (): void {
    Route::get('/account/setup', [AccountSecurityController::class, 'setup'])->name('membership.account.setup');
    Route::get('/account/security', [AccountSecurityController::class, 'edit'])->name('membership.account.security');
    Route::put('/account/password', [AccountSecurityController::class, 'updatePassword'])->middleware('throttle:10,1')->name('membership.account.password.update');
    Route::post('/logout', [MemberAuthController::class, 'logout'])->name('logout');

    Route::middleware(EnsurePasswordSetup::class)->group(function (): void {
        Route::get('/profile', MembershipProfileController::class)->name('membership.profile');
        Route::get('/tracking', [MemberPortalController::class, 'tracking'])->name('membership.tracking.index');
        Route::get('/tracking/{tracking}', [MemberPortalController::class, 'trackingDetail'])->name('membership.tracking.show');
        Route::get('/bills', [MemberPortalController::class, 'bills'])->name('membership.bills.index');
        Route::get('/bills/{bill}', [MemberPortalController::class, 'billDetail'])->name('membership.bills.show');

        Route::prefix('admin')->name('admin.')->middleware(EnsureAdmin::class)->group(function (): void {
            Route::get('/', Admin\DashboardController::class)->name('dashboard');
            Route::get('testimonials', [Admin\TestimonialController::class, 'index'])->name('testimonials.index');
            Route::put('testimonials/{testimonial}', [Admin\TestimonialController::class, 'update'])->name('testimonials.update');
            Route::post('catalogs/upload', [CatalogImageController::class, 'upload'])->middleware('throttle:30,1')->name('catalogs.upload');
            Route::get('catalogs/upload-preview', [CatalogImageController::class, 'preview'])->name('catalogs.upload-preview');
            Route::resource('catalogs', Admin\InvitationCatalogController::class)->except('show');
            Route::resource('discounts', Admin\CatalogDiscountController::class)->except('show');
            Route::resource('catalog-categories', Admin\CatalogCategoryController::class)->parameters(['catalog-categories' => 'category'])->only(['index', 'store', 'update', 'destroy']);
            Route::resource('members', Admin\MemberController::class)->except('show');
            Route::get('points', [Admin\PointController::class, 'index'])->name('points.index');
            Route::post('points', [Admin\PointController::class, 'store'])->name('points.store');
            Route::post('point-rules/mode', [Admin\PointRuleController::class, 'mode'])->name('point-rules.mode');
            Route::post('point-rules/purchase', [Admin\PointRuleController::class, 'purchase'])->name('point-rules.purchase');
            Route::resource('point-rules', Admin\PointRuleController::class)->except(['show', 'create', 'edit']);
            Route::resource('services', Admin\ServiceController::class)->except('show');
            Route::resource('statuses', Admin\StatusController::class)->except('show');
            Route::resource('tracking', Admin\TrackingController::class)->except('destroy');
            Route::resource('bills', Admin\BillController::class)->except('destroy');
            Route::post('bills/{bill}/pay', [Admin\BillController::class, 'pay'])->name('bills.pay');
            Route::post('bills/{bill}/cancel', [Admin\BillController::class, 'cancel'])->name('bills.cancel');
            Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        });
    });
});
