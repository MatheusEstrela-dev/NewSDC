<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TelescopeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
        if (!class_exists(\Laravel\Telescope\Telescope::class)) {
            return;
        }

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        \Laravel\Telescope\Telescope::filter(function (\Laravel\Telescope\IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
        */
    }

    public function boot(): void
    {
        /*
        if (!class_exists(\Laravel\Telescope\Telescope::class)) {
            return;
        }

        $this->gate();
        */
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        \Laravel\Telescope\Telescope::hideRequestParameters(['_token']);

        \Laravel\Telescope\Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    protected function gate(): void
    {
        \Illuminate\Support\Facades\Gate::define('viewTelescope', function (\App\Models\User $user) {
            return true;
        });
    }
}
