<?php

return [

    'use' => 'production',

    'properties' => [

        'production' => [
            'host'                => env('RABBITMQ_HOST'),
            'port'                => env('RABBITMQ_PORT'),
            'username'            => env('RABBITMQ_USER'),
            'password'            => env('RABBITMQ_PASSWORD'),
            'vhost'               => env('RABBITMQ_VHOST'),
            'exchange'            => env('RABBITMQ_EXCHANGE', 'microservices.topic'),
            'exchange_type'       => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),
            'consumer_tag'        => 'consumer',
            'ssl_options'         => [], // See https://secure.php.net/manual/en/context.ssl.php
            'connect_options'     => [], // See https://github.com/php-amqplib/php-amqplib/blob/master/PhpAmqpLib/Connection/AMQPSSLConnection.php
            'queue_properties'    => ['x-ha-policy' => ['S', 'all']],
            'exchange_properties' => [],
            'timeout'             => 0
        ],

    ],
    'interval_connection' => env('RABBITMQ_INTERVAL_CONNECTION', 5)
];
