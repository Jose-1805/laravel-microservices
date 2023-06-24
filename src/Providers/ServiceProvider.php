<?php

namespace Jose1805\LaravelMicroservices\Providers;

use Illuminate\Support\ServiceProvider as SP;
use Jose1805\LaravelMicroservices\Console\Commands\ApiGateway\ServiceConnectionCommand;
use Jose1805\LaravelMicroservices\Console\Commands\ConsumeAmqpCommand;
use Jose1805\LaravelMicroservices\Console\Commands\MakeResolverCommand;
use Jose1805\LaravelMicroservices\Console\Commands\Service\MakeAccessTokenCommand;
use Jose1805\LaravelMicroservices\Console\Commands\Service\MakeResourceCommand;

class ServiceProvider extends SP
{
    public function register()
    {
        if(config('microservices.is_api_gateway')) {
            // Middleware para autenticar usuarios desde un micro servicio
            app('router')->aliasMiddleware('auth_service_user', \Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway\AuthenticateServiceUser::class);
            app('router')->aliasMiddleware('teams', \Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway\TeamsPermission::class);

            $this->commands([
                // Comando para recursos y configuraciones para acceder a un servicio
                ServiceConnectionCommand::class
            ]);
        } else {
            // Middleware para autenticar las peticiones desde el api gateway al micro servicio
            app('router')->aliasMiddleware('auth_api_gateway', \Jose1805\LaravelMicroservices\Http\Middleware\Service\AuthenticateAccessMiddleware::class);
            // Middleware para asignar al request el usuario enviado desde el api gateway
            app('router')->aliasMiddleware('set_user_request', \Jose1805\LaravelMicroservices\Http\Middleware\Service\SetUserRequest::class);

            $this->commands([
                // Commando para crear un token de acceso al servicio
                MakeAccessTokenCommand::class,
                // Commando para crear recursos de un servicio
                MakeResourceCommand::class,
            ]);
        }

        $this->commands([
            // Comando worker para conectarse a RabbitMQ
            ConsumeAmqpCommand::class,
            // Comando para crear una clase que soluciona peticiones en segundo plano
            MakeResolverCommand::class,
        ]);


        $this->mergeConfigFrom(
            __DIR__.'/../../config/microservices.php',
            'microservices'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/background_'.$this->getTargetName().'.php',
            'background'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../../config/amqp.php',
            'amqp'
        );
    }

    public function boot()
    {
        // COnfiguración inicial para establecer parámetros
        $this->publishes([
            __DIR__.'/../../config/microservices.php' => config_path('microservices.php'),
        ], 'start-config-laravel-microservices');

        // Configuración de tareas en segundo plano
        $this->publishes([
            __DIR__.'/../../config/background_'.$this->getTargetName().'.php' => config_path('background.php'),
        ], 'laravel-microservices-config');


        if(config('microservices.is_api_gateway')) {
            // COnfiguraciones necesarias para el api gateway
            $this->publishes([
                __DIR__.'/../../database/migrations/'.(config('microservices.use_uuid') ? 'uuid' : 'bigint').'/laravel_microservices_create_users_table.php' => database_path('migrations/' . date('Y_m_d_His', time()).'_laravel_microservices_create_users_table.php'),
                __DIR__.'/../../database/seeders/services/AllServices.php' => database_path('seeders/services/AllServices.php'),
                __DIR__.'/../../database/seeders/RolesAndPermissionsSeeder'.(config('permission.teams') ? 'Teams' : '').'.php' => database_path('seeders/RolesAndPermissionsSeeder.php'),
                __DIR__.'/../../database/seeders/UserAdmin'.(config('permission.teams') ? 'Teams' : '').'.php' => database_path('seeders/UserAdmin.php'),
            ], 'laravel-microservices-config');

            // Rutas iniciales del api gateway
            $this->loadRoutesFrom(__DIR__.'/../../routes/ApiGateway/api.php');

            // Configuración de migraciones necesarias en el api gateway
            if(config('microservices.use_uuid')) {
                $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/uuid/required');
            } else {
                $this->loadMigrationsFrom(__DIR__.'/../../database/migrations/bigint/required');
            }
        }
    }

    /**
     * Tipo de proyecto (service o api_gateway)
     *
     * @return string
     */
    public function getTargetName(): string
    {
        return config('microservices.is_api_gateway') ? 'api_gateway' : 'service';
    }
}
