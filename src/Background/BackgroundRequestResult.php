<?php

namespace Jose1805\LaravelMicroservices\Background;

use Jose1805\LaravelMicroservices\Background\src\Resolver;
use Jose1805\LaravelMicroservices\Models\BackgroundRequest;

class BackgroundRequestResult implements Resolver
{
    public function handle($data): array
    {
        $background_request = BackgroundRequest::where('id', $data['id'])
        ->where('event', $data['event'])->first();

        if($background_request) {
            $background_request->update([
                'output_data' => $data['output_data'],
                'state' => '1'
            ]);
        }

        return [
            // Respuesta que se enviará en el campo output_data
            'response' => [],

            // En las opciones puede configurar el evento y cola de respuesta que por defecto son
            // background_request_result y api_gateway_queue.default respectivamente
            // ej ['event' => 'new_event', 'queue' => 'new_queue', 'publish' => true, 'response_keys' = ['id', 'output_data']]
            // con la propiedad publish puede hacer que no se envíe la respuesta por defecto se envía id, event, output_data
            // con la propiedad response_keys puede definir que campos enviar en la respuesta
            'options' => ['publish' => false],
        ];
    }
}
