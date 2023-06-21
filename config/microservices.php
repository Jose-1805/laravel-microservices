<?php

return [
    // Define si el paquete se utiliza para administrar un micro servicio (false) o un api gateway (true)
    'is_api_gateway' => false,
    // Tokens de acceso de un servicio, se utilizan únicamente en los micro servicios para validar peticiones del api gateway
    // no se debe agregar la variable de entorno ACCESS_TOKENS ya que se hace a través del comando lm:make-access-token
    'access_tokens' => env('ACCESS_TOKENS', ''),
    // Determina si los recursos de configuran con identificadores enteros auto incrementales o con uuids
    'use_uuid' => true,
];
