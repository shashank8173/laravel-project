<?php

namespace App\Providers;

use App\Auth\LegacyEmployeeProvider;
use App\Models\BrandingSetting;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Policies\ProjectPolicy;
use App\Policies\ProjectTaskPolicy;
use App\View\Composers\LayoutComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::provider('legacy-employee', function ($app, array $config) {
            return new LegacyEmployeeProvider($app['hash'], $config['model']);
        });

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ProjectTask::class, ProjectTaskPolicy::class);

        \Illuminate\Pagination\Paginator::useBootstrapFive();

        View::composer(['layouts.app', 'layouts.topbar', 'layouts.sidebar'], LayoutComposer::class);

        View::composer('*', function ($view) {
            static $logo = null;
            static $icon = null;
            static $loaded = false;

            if (! $loaded) {
                $loaded = true;
                $logo = asset('assets/img/logo2.png');
                $icon = $logo;
                try {
                    if (Schema::hasTable('branding_settings')) {
                        $branding = BrandingSetting::current();
                        $logo = $branding->logoUrl();
                        $icon = $branding->iconUrl();
                    }
                } catch (\Throwable) {
                    // Keep defaults during migrate / missing DB
                }
            }

            $view->with([
                'appLogoUrl' => $logo,
                'appIconUrl' => $icon,
            ]);
        });
    }
}
