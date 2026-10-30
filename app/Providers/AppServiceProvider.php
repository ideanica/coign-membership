<?php

namespace App\Providers;

use App\Database\NeonPostgresConnector;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('db.connector.pgsql', NeonPostgresConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        View::share('assetVersion', static fn (string $path): string => ($modifiedAt = @filemtime(public_path($path))) !== false ? '?v='.$modifiedAt : '');
        RateLimiter::for('member-email-send', fn (Request $request) => Limit::perHour(5)->by('member-email-send:'.$request->user()->id));
        RateLimiter::for('member-email-verify', fn (Request $request) => Limit::perMinute(10)->by('member-email-verify:'.$request->user()->id));
    }
}
