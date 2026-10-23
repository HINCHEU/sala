<?php

namespace App\Providers;

use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Policies\ExamPolicy;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentSchool::class);

        // Telescope is a dev dependency: absent from the production image, so register it only when installed.
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::define('platform', fn (User $user) => $user->is_platform_admin);

        Gate::policy(Exam::class, ExamPolicy::class);

        // Platform admins have support access to every school.
        Gate::before(fn (User $user) => $user->is_platform_admin ? true : null);
    }
}
