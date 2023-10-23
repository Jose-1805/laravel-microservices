<?php

namespace Jose1805\LaravelMicroservices\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

trait ServiceConsumer
{
    // Usuario logueado al realizar la petición
    public $user_id = null;

    /**
     * Solicitud http a un servicio del cluster
     * @param $method
     * @param $requestUrl
     * @param array $formParams
     * @param bool $isFile
     * @return mixed
     */
    public function performRequest($method, $requestUrl, $formParams = [], $isFile = false): mixed
    {
        $func = strtolower($method);

        $request = Http::baseUrl(config('laravel_microservices.microservices.' . strtolower($this->name) . '.base_uri'))->withHeaders([
            'Authorization' => config('laravel_microservices.microservices.' . strtolower($this->name) . '.access_token'),
            'UserId' => $this->user_id
        ]);

        $has_file = false;

        foreach ($formParams as $key => $value) {
            if ($value instanceof UploadedFile) {
                $has_file = true;
            }
        }

        if ($has_file) {
            foreach ($formParams as $key => $value) {
                if ($value instanceof UploadedFile) {
                    $request = $request->attach($key, $value->get(), $value->getClientOriginalName());
                }
            }
        } else {
            if ($func != 'get' && $func != 'delete') {
                $request = $request->asForm();
            }
        }

        $response = $request->$func($requestUrl, $formParams);

        $data = json_decode($response->body(), true);

        if ($data == null) {
            if ($isFile && $response->successful()) {
                return response()->streamDownload(function () use ($response) {
                    echo $response->body();
                }, '', $response->headers());
            }
            $data = ['error' => config('app.debug') && strlen($response->body()) ? $response->body() : 'Error interno del servidor', 'code' => 500];
        }

        return $data;
    }

    /**
     * Lista de elementos del servicio
     *
     * @return array
     */
    public function getElements($params = []): array
    {
        return $this->performRequest('GET', config('laravel_microservices.microservices.' . strtolower($this->name) . '.path'), $params);
    }

    /**
     * Obtiene un elemento especifico del servicio
     *
     * @param string $id
     * @return array
     */
    public function getElement($id): array
    {
        return $this->performRequest('GET', config('laravel_microservices.microservices.' . strtolower($this->name) . '.path') . '/' . $id);
    }

    /**
     * Registro de un nuevo elemento en el servicio
     *
     * @param array $data
     * @return array
     */
    public function createElement($data): array
    {
        return $this->performRequest('POST', config('laravel_microservices.microservices.' . strtolower($this->name) . '.path'), $data);
    }

    /**
     * Actualiza el elemento especificado
     *
     * @param string $id
     * @param array $data
     * @return array
     */
    public function updateElement($id, $data): array
    {
        return $this->performRequest('PUT', config('laravel_microservices.microservices.' . strtolower($this->name) . '.path') . '/' . $id, $data);
    }

    /**
     * Elimina el elemento especificado
     *
     * @param string $id
     * @return array
     */
    public function destroyElement($id): array
    {
        return $this->performRequest('DELETE', config('laravel_microservices.microservices.' . strtolower($this->name) . '.path') . '/' . $id);
    }
}
