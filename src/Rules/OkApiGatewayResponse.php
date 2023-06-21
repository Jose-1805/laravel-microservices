<?php

namespace Jose1805\LaravelMicroservices\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Jose1805\LaravelMicroservices\Traits\ApiGatewayConsumer;

class OkApiGatewayResponse implements ValidationRule
{
    use ApiGatewayConsumer;

    protected $method;
    protected $path;
    protected $params;
    protected $is_file;
    protected $concat_value_to_path;
    protected $message;

    /**
     * Constructor
     *
     * @param string $path                      Ruta a donde se ejecuta la petición
     * @param string $method                    Tipo de método HTTP de la ruta
     * @param boolean $concat_value_to_path     Determina si el valor recibido en la validación se debe
     *                                          concatenar a $path
     * @param array $params                     Parámetros adicionales para enviar en la solicitud
     * @param boolean $is_file                  Determina si la ruta genera un archivo como respuesta
     */
    public function __construct(string $path, $method = 'GET', $concat_value_to_path = true, $params = [], $is_file = false, $message = 'El campo :attribute seleccionado no existe')
    {
        $this->path = $path;
        $this->method = $method;
        $this->params = $params;
        $this->is_file = $is_file;
        $this->concat_value_to_path = $concat_value_to_path;
        $this->message = $message;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $final_path = $this->path;
        if (substr($this->path, -1) != '/') {
            $final_path .= '/';
        }
        $final_path .= $value;

        $response = $this->performRequest($this->method, $final_path, $this->params, $this->is_file);
        // Si se obtiene una respuesta válida del Api Gateway y es un error
        if (is_array($response) && array_key_exists('code', $response) && (intval($response['code']) < 200 || intval($response['code']) >= 300)) {
            $fail($this->message);
        }
    }
}
