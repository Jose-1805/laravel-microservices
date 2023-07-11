<?php

namespace Jose1805\LaravelMicroservices\Traits;

use Jose1805\LaravelMicroservices\Enums\BackgroundRequestState;
use Jose1805\LaravelMicroservices\Models\BackgroundRequest;
use Illuminate\Http\Request;

trait BackgroundRequestGenerator
{
    use JsonRequestConverter;
    use ApiResponser;

    /**
     * Almacena un request para que sea resuelto en segundo plano
     *
     * @param Request $request
     * @param string $event             Nombre de evento para la solicitud
     * @param boolean $http_response    Determina el formato de respuesta
     * @return array
     */
    public function resolveInBackground(Request $request, string $event, $http_response = true): mixed
    {
        $user = $request->user() ? $request->user()->id : null;
        $background_request = BackgroundRequest::create([
            'event' => $event,
            'state' => BackgroundRequestState::InQueue,
            'input_data' => $this->requestToJson($request),
            'user_id' => $user
        ]);

        $background_request->publish($event, $this->queue);

        $data = [
            'id' => $background_request->id,
            'event' => $background_request->event,
        ];

        return $http_response ? $this->httpOkResponse($data) : $data;
    }
}
