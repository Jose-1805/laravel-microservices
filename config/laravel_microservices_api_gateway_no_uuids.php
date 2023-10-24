<?php

$permissions = [
    'name-permission-1' => 'name-permission-1',
    'name-permission-2' => 'name-permission-2',
];

return [
    // Define si el paquete se utiliza para administrar un micro servicio (false) o un api gateway (true)
    'is_api_gateway' => true,

    // Determina si los recursos de configuran con identificadores enteros auto incrementales o con uuids
    'use_uuid' => false,

    // Configuraciones para ejecución de tareas en segundo plano
    'background' => [
        // Define las clases con las que se resuelven las solicitudes en segundo plano
        'events' => [
            'background_request_result' => \Jose1805\LaravelMicroservices\Background\BackgroundRequestResult::class,
        ],
    ],

    'microservices' => [
        /*
        // Nombre del microservicio
        'example_microservice_name' => [
            // Url base para conectarse al servicio
            'base_uri' => env('EXAMPLE_MICROSERVICE_NAME_BASE_URI', 'https://domain-service.com'),
            // Path base para acceder a las funciones básicas CRUD del servicio
            'path' => env('EXAMPLE_MICROSERVICE_NAME_PATH', '/api/path'),
            // Token para poder acceder a las funciones del servicio de manera autenticada
            'access_token' => env('EXAMPLE_MICROSERVICE_NAME_ACCESS_TOKEN', '12345678'),
            // Cola a la que el servicio se conecta para recibir mensajes u ordenes de ejecución
            'queue' => env('EXAMPLE_MICROSERVICE_NAME_QUEUE', 'service_name.queue'),
        ],
        */
    ],

    'roles' => [
        /*'role_team_example' => [
            'team' => 'team_name',
            'permissions' => [],
        ],
        'general_role_example' => [
            'team' => null,
            'permissions' => [
                $permissions['see-orders'],
                $permissions['create-orders'],
                $permissions['execute-automatic-order-processing'],
                $permissions['execute-manual-order-processing'],
                $permissions['update-order-state'],
                $permissions['cancel-orders'],
                $permissions['manage-order-tracking'],
            ]
        ],*/
    ],

    'permissions' => $permissions,
];
