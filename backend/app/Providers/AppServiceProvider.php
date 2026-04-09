<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\UserPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
