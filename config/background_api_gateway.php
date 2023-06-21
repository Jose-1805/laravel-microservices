<?php

return [
    // Define las clases con las que se resuelven las solicitudes en segundo plano
    'events' => [
        // Evento para manejar el resultado de las tareas en segundo plano iniciadas por el api gateway
        'background_request_result' => \Jose1805\LaravelMicroservices\Background\BackgroundRequestResult::class,
    ],
];
