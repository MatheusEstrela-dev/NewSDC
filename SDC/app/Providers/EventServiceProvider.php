<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Log queries lentas (> 1 segundo) - Sistema Crítico 24/7
        \DB::listen(function ($query) {
            $threshold = env('QUERY_SLOW_THRESHOLD', 1000); // 1 segundo padrão

            if ($query->time > $threshold) {
                \Log::channel('queries')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                ]);

                \App\Services\Logging\ActivityLogger::logPerformance(
                    operation: 'slow_query',
                    duration: $query->time,
                    metrics: [
                        'sql' => $query->sql,
                        'threshold' => $threshold,
                    ]
                );
            }
        });

        // Log jobs falhados automaticamente
        \Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
            \Log::channel('jobs')->error('Job failed', [
                'job' => $event->job->resolveName(),
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'exception' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString(),
            ]);

            \App\Services\Logging\ActivityLogger::logCriticalError(
                message: 'Queue job failed: ' . $event->job->resolveName(),
                exception: $event->exception,
                context: [
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                    'attempts' => method_exists($event->job, 'attempts') ? $event->job->attempts() : 0,
                ]
            );
        });

        // Log jobs processados com sucesso (para métricas)
        \Queue::after(function (\Illuminate\Queue\Events\JobProcessed $event) {
            \App\Services\Logging\ActivityLogger::logEvent(
                type: 'jobs',
                event: 'processed',
                data: [
                    'job' => $event->job->resolveName(),
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                ],
                level: 'info'
            );
        });

        // Log tentativas de login
        Event::listen('Illuminate\Auth\Events\Login', function ($event) {
            \App\Services\Logging\ActivityLogger::logSecurity(
                event: 'login_success',
                data: [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'guard' => $event->guard,
                ],
                severity: 'info'
            );
        });

        Event::listen('Illuminate\Auth\Events\Failed', function ($event) {
            \App\Services\Logging\ActivityLogger::logSecurity(
                event: 'login_failed',
                data: [
                    'email' => $event->credentials['email'] ?? 'unknown',
                    'guard' => $event->guard ?? 'web',
                ],
                severity: 'warning'
            );
        });

        Event::listen('Illuminate\Auth\Events\Logout', function ($event) {
            \App\Services\Logging\ActivityLogger::logSecurity(
                event: 'logout',
                data: [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'guard' => $event->guard,
                ],
                severity: 'info'
            );
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
