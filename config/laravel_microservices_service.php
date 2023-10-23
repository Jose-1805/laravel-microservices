<?php

return [
    // Define si el paquete se utiliza para administrar un micro servicio (false) o un api gateway (true)
    'is_api_gateway' => false,

    // Tokens de acceso para validar peticiones realizadas desde el api gateway
    // no es necesario agregar la variable de entorno ACCESS_TOKENS ya que se hace a través del comando lm:make-access-token
    'access_tokens' => env('ACCESS_TOKENS', ''),

    // Configuraciones para ejecución de tareas en segundo plano
    'background' => [
        // Define el nombre del evento para enviar el resultado de una ejecución en segundo plano
        'event_response' => env('BACKGROUND_EVENT_RESPONSE', 'background_request_result'),
        // Define a que cola se debe enviar el resultado de una ejecución en segundo plano
        'queue_response' => env('BACKGROUND_QUEUE_RESPONSE', 'api_gateway_queue.default'),
        // Define las clases con las que se resuelven las solicitudes en segundo plano
        // 'event_name' => \namespace\ResolverExample::class,
        'events' => [
        ],
    ],

    // Configuración de acceso al api gateway
    'api_gateway' => [
        'public_url' => env('API_GATEWAY_PUBLIC_URL'),
        'base_uri' => env('API_GATEWAY_BASE_URI'),
        'access_token' => env('API_GATEWAY_ACCESS_TOKEN'),
    ]
];
