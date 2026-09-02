<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Token
    |--------------------------------------------------------------------------
    |
    | Token exigido no header X-Metrics-Token do GET /api/health/metrics.
    | Fora de local/testing o endpoint fica fail-closed (503) se o token nao
    | estiver configurado — as metricas expoem detalhes do runtime.
    |
    */

    'metrics_token' => env('METRICS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'pt_BR',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'pt_BR',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store' => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\FilesystemServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // Necessario para tempo real: registra /broadcasting/auth e carrega
        // routes/channels.php.
        //
        // Ficou desligado por um tempo porque o boot resolve o broadcaster do
        // driver ativo, e com BROADCAST_CONNECTION=reverb sem
        // pusher/pusher-php-server a aplicacao caia no boot com
        // Class "Pusher\Pusher" not found.
        //
        // Isso deixou de ser um problema, e a nota anterior pedia um
        // `composer require` que nao e mais necessario: pusher/pusher-php-server
        // entra transitivamente com laravel/reverb (que pede ^7.2), esta travado
        // no composer.lock em 7.2.8 e presente no vendor da imagem.
        //
        // O risco residual e a natureza do boot, e ele e concreto: channels.php
        // chama Broadcast::channel(), que resolve o broadcaster de forma eager.
        // Com BROADCAST_CONNECTION=reverb e sem REVERB_APP_KEY, o Pusher recebe
        // null e a aplicacao INTEIRA morre no boot -- nao apenas o tempo real:
        //
        //   Pusher\Pusher::__construct(): Argument #1 ($auth_key) must be of
        //   type string, null given
        //
        // Ou seja: BROADCAST_CONNECTION e as REVERB_* andam JUNTAS, em qualquer
        // ambiente. Definir uma sem a outra e o unico jeito de derrubar o SDC
        // mexendo em tempo real.
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\OctaneServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,

        // Module Service Providers
        App\Modules\Rat\RatServiceProvider::class,
        App\Modules\Demandas\DemandasServiceProvider::class,
        App\Modules\Tdap\TdapServiceProvider::class,
        App\Modules\Decretacoes\DecretacoesServiceProvider::class,
        App\Modules\Pmda\PmdaServiceProvider::class,
        App\Modules\AjudaHumanitaria\AjudaHumanitariaServiceProvider::class,
        App\Modules\Treinamento\TreinamentoServiceProvider::class,
        App\Modules\Inmet\InmetServiceProvider::class,
        App\Modules\Medalhao\MedalhaoServiceProvider::class,
        App\Modules\Sismos\SismosServiceProvider::class,
        App\Modules\Suporte\SuporteServiceProvider::class,
        App\Modules\PlanCon\PlanConServiceProvider::class,
        App\Modules\Dashboard\DashboardServiceProvider::class,
        App\Modules\Compdec\CompdecServiceProvider::class,
        App\Modules\Cisterna\CisternaServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
        'Auth' => Illuminate\Auth\AuthManager::class,
    ])->toArray(),

];
