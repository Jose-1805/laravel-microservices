<?php

return [
    // Define el nombre del evento para enviar el resultado de una ejecución en segundo plano
    'event_response' => env('BACKGROUND_EVENT_RESPONSE', 'background_request_result'),
    // Define a que cola se debe enviar el resultado de una ejecución en segundo plano
    'queue_response' => env('BACKGROUND_QUEUE_RESPONSE', 'api_gateway_queue.default'),
    // Define las clases con las que se resuelven las solicitudes en segundo plano
    'events' => [
    ],
];
