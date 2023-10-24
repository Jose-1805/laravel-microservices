<?php

namespace Jose1805\LaravelMicroservices\Providers;

use App\Console\Commands\SyncRolesAndPermissions;
use Illuminate\Support\ServiceProvider as SP;
use Jose1805\LaravelMicroservices\Console\Commands\ApiGateway\SyncMicroserviceConnections;
use Jose1805\LaravelMicroservices\Console\Commands\ConsumeAmqp;
use Jose1805\LaravelMicroservices\Console\Commands\MakeResolver;
use Jose1805\LaravelMicroservices\Console\Commands\Service\MakeAccessToken;
use Jose1805\LaravelMicroservices\Console\Commands\Service\MakeResource;

class ServiceProvider extends SP
{
    public function register()
    {
        if(config('laravel_microservices.is_api_gateway')) {
            // Middleware para autenticar usuarios desde un micro servicio
            app('router')->aliasMiddleware('auth_service_user', \Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway\AuthenticateServiceUser::class);
            app('router')->aliasMiddleware('teams', \Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway\TeamsPermission::class);
            app('router')->aliasMiddleware('request_from_micro_service', \Jose1805\LaravelMicroservices\Http\Middleware\ApiGateway\RequestFromMicroService::class);

            $this->commands([
                // Comando para recursos y configuraciones para acceder a un microservicio
                SyncMicroserviceConnections::class,
                // Comando para crear roles y permisos del sistema
                SyncRolesAndPermissions::class,
            ]);
        } else {
            // Middleware para autenticar las peticiones desde el api gateway al micro servicio
            app('router')->aliasMiddleware('auth_api_gateway', \Jose1805\LaravelMicroservices\Http\Middleware\Service\AuthenticateAccessMiddleware::class);
            // Middleware para asignar al request el usuario enviado desde el api gateway
            app('router')->aliasMiddleware('set_user_request', \Jose1805\LaravelMicroservices\Http\Middleware\Service\SetUserRequest::class);

            $this->commands([
                // Commando para crear un token de acceso al servicio
                MakeAccessToken::class,
                // Commando para crear recursos de un servicio
                MakeResource::class,
            ]);
        }

        $this->commands([
            // Comando worker para conectarse a RabbitMQ
            ConsumeAmqp::class,
            // Comando para crear una clase que soluciona peticiones en segundo plano
            MakeResolver::class,
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/laravel_microservices_' . $this->getTargetName() . '.php',
            'laravel_microservices'
        );
    }

    public function boot()
    {
        // Configuración del paquete para api gateway
        $this->publishes([
            __DIR__ . '/../../config/laravel_microservices_api_gateway.php' => config_path('laravel_microservices.php'),
            __DIR__ . '/../../database/migrations/uuid/laravel_microservices_create_users_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_laravel_microservices_create_users_table.php'),
        ], 'laravel-microservices-config-api-gateway');

        // Configuración del paquete para api gateway sin uuids
        $this->publishes([
            __DIR__ . '/../../config/laravel_microservices_api_gateway_no_uuids.php' => config_path('laravel_microservices.php'),
            __DIR__ . '/../../database/migrations/bigint/laravel_microservices_create_users_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_laravel_microservices_create_users_table.php'),
        ], 'laravel-microservices-config-api-gateway-no-uuids');

        // Configuración del paquete para microservicios
        $this->publishes([
            __DIR__ . '/../../config/laravel_microservices_microservice.php' => config_path('laravel_microservices.php'),
        ], 'laravel-microservices-config-microservice');

        if(config('laravel_microservices.is_api_gateway')) {
            // Rutas iniciales del api gateway
            $this->loadRoutesFrom(__DIR__ . '/../../routes/ApiGateway/api.php');

            // Configuración de migraciones necesarias en el api gateway
            if(config('laravel_microservices.use_uuid')) {
                $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/uuid/required');
            } else {
                $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/bigint/required');
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
        return config('laravel_microservices.is_api_gateway') ? 'api_gateway' : 'microservice';
    }
}
