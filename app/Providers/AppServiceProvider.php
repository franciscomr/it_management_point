<?php

namespace App\Providers;

use App\Modules\Shared\Services\TenantManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // scoped() para el manejo global del tenant: se reinicia por request/job.
        $this->app->scoped(
            TenantManager::class,
            fn() => new TenantManager()
        );

        $this->app->bind(
            \App\Modules\Shared\Contracts\TenantResolverInterface::class,
            \App\Modules\Shared\Services\HeaderTenantResolver::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->loadMigrationsFrom(base_path('app/Modules/Shared/Database/migrations'));

    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
