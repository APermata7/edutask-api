<?php

namespace App\Providers;

use App\Modules\Assignments\Models\Assignment;
use App\Modules\Assignments\Policies\AssignmentPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Assignment::class, AssignmentPolicy::class);
    }
}
