<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Log\Context\Repository as ContextRepository;

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
        if (app()->environment('production')) {
            \URL::forceScheme('https');
        }

        if (app()->runningInConsole() === false) {
            $requestId = (string) Str::uuid();

            if (class_exists(\Illuminate\Support\Facades\Context::class)) {
                \Illuminate\Support\Facades\Context::add('request_id', $requestId);
                \Illuminate\Support\Facades\Context::add('environment', config('app.env'));
                \Illuminate\Support\Facades\Context::add('app_name', config('app.name'));
            } else {
                Log::withContext([
                    'request_id' => $requestId,
                    'environment' => config('app.env'),
                    'app_name' => config('app.name'),
                ]);
            }

            if (request()) {
                request()->headers->set('X-Request-ID', $requestId);
            }
        }

        DB::listen(function ($query) {
            $threshold = config('app.env') === 'production' ? 1000 : 2000;

            if ($query->time > $threshold) {
                Log::channel('queries')->warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                    'url' => request()?->fullUrl(),
                    'method' => request()?->method(),
                    'user_id' => auth()->id(),
                    'threshold_ms' => $threshold,
                    'severity' => $query->time > ($threshold * 2) ? 'critical' : 'warning',
                ]);

                if ($query->time > ($threshold * 2)) {
                    Log::channel('critical')->error('Critical Slow Query', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                        'url' => request()?->fullUrl(),
                    ]);
                }
            }
        });

        if (app()->runningInConsole()) {
            $commandId = (string) Str::uuid();

            if (class_exists(\Illuminate\Support\Facades\Context::class)) {
                \Illuminate\Support\Facades\Context::add('command_id', $commandId);
                \Illuminate\Support\Facades\Context::add('is_console', true);
            } else {
                Log::withContext([
                    'command_id' => $commandId,
                    'is_console' => true,
                ]);
            }
        }

        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Redefinição de Senha - SDC')
                ->view('emails.password_reset_simple', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                    'cpf_coordenador' => $notifiable->cpf ?? null,
                ]);
        });
    }
}
