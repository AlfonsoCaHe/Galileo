<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\AdminCheck;
use App\Http\Middleware\AlumnoCheck;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\TutorLaboralCheck;
use App\Http\Middleware\SetProjectConnection;

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
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Registro de todos los aliases de middlewares de ruta.
        // Se llama a Route::aliasMiddleware() directamente dentro de boot().
        Route::aliasMiddleware('AdminCheck', AdminCheck::class);
        Route::aliasMiddleware('AlumnoCheck', AlumnoCheck::class);
        Route::aliasMiddleware('ProfesorCheck', ProfesorCheck::class);
        Route::aliasMiddleware('TutorLaboralCheck', TutorLaboralCheck::class);
        Route::aliasMiddleware('SetProjectConnection', SetProjectConnection::class); 

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
