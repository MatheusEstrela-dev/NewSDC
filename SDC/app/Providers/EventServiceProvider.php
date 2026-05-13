<?php

namespace App\Providers;

use App\Listeners\PermissionEventSubscriber;
use App\Models\Role;
use App\Models\User;
use App\Observers\RoleObserver;
use App\Observers\UserObserver;
use App\Services\Logging\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);

        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        Event::subscribe(PermissionEventSubscriber::class);

        Event::listen(JobFailed::class, function (JobFailed $event) {
            Log::channel('jobs')->error('Job failed', [
                'job'        => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'queue'      => $event->job->getQueue(),
                'exception'  => $event->exception->getMessage(),
                'trace'      => $event->exception->getTraceAsString(),
            ]);

            ActivityLogger::logCriticalError(
                message:   'Queue job failed: ' . $event->job->resolveName(),
                exception: $event->exception,
                context:   [
                    'queue'      => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                    'attempts'   => method_exists($event->job, 'attempts') ? $event->job->attempts() : 0,
                ]
            );
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event) {
            ActivityLogger::logEvent(
                type:  'jobs',
                event: 'processed',
                data:  [
                    'job'        => $event->job->resolveName(),
                    'connection' => $event->connectionName,
                    'queue'      => $event->job->getQueue(),
                ],
                level: 'info'
            );
        });

        Event::listen(Login::class, function (Login $event) {
            ActivityLogger::logSecurity(
                event:    'login_success',
                data:     [
                    'user_id' => $event->user->id,
                    'email'   => $event->user->email,
                    'guard'   => $event->guard,
                ],
                severity: 'info'
            );
        });

        Event::listen(Failed::class, function (Failed $event) {
            ActivityLogger::logSecurity(
                event:    'login_failed',
                data:     [
                    'email' => $event->credentials['email'] ?? 'unknown',
                    'guard' => $event->guard ?? 'web',
                ],
                severity: 'warning'
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            ActivityLogger::logSecurity(
                event:    'logout',
                data:     [
                    'user_id' => $event->user->id,
                    'email'   => $event->user->email,
                    'guard'   => $event->guard,
                ],
                severity: 'info'
            );
        });
    }
}
