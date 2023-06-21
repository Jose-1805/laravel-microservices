<?php

namespace Jose1805\LaravelMicroservices\Traits;

use Illuminate\Http\JsonResponse;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

trait ApiResponser
{
    /**
     * Genera una respuesta JSON estándar
     *
     * @param string|array $data
     * @param int $code
     * @return JsonResponse
     */
    public function generateResponse($data, $code): JsonResponse
    {
        switch ($code) {
            case Response::HTTP_ACCEPTED:
            case Response::HTTP_CREATED:
            case Response::HTTP_OK:
                $final_data = is_array($data) ? (array_key_exists('data', $data) && array_key_exists('code', $data) ? $data['data'] : $data) : $data;
                return \response()->json(['data' => $final_data, 'code' => $code], $code);
                break;
            default:
                $final_data = is_array($data) ? (array_key_exists('error', $data) && array_key_exists('code', $data) ? $data['error'] : $data) : $data;
                return \response()->json(['error' => $data, 'code' => $code], $code);
                break;
        }
    }
    /**
     * Genera una respuesta JSON estándar a partir de la respuesta de un servicio
     *
     * @param array $data
     * @return JsonResponse
     */
    public function generateResponseByService($data): JsonResponse
    {
        if (array_key_exists('data', $data) && array_key_exists('code', $data)) {
            return $this->generateResponse($data['data'], $data['code']);
        } elseif (array_key_exists('error', $data) && array_key_exists('code', $data)) {
            return $this->generateResponse($data['error'], $data['code']);
        } elseif (array_key_exists('error', $data)) {
            return $this->generateResponse($data['error'], 500);
        }

        return $this->generateResponse($data, 500);
    }

    /**
     * Genera una respuesta con código de respuesta HTTP_OK 200
     *
     * @param string|array $data
     * @return JsonResponse
     */
    public function httpOkResponse($data = 'Success.'): JsonResponse
    {
        return $this->generateResponse($data, Response::HTTP_OK);
    }

    /**
     * Genera una respuesta con código de respuesta HTTP_UNAUTHORIZED 401
     *
     * @param string|array $data
     * @return JsonResponse
     */
    public function httpUnauthorizedResponse(): JsonResponse
    {
        return $this->generateResponse('Unauthorized.', Response::HTTP_UNAUTHORIZED);
    }

    public function renderExceptions($request, Throwable $exception)
    {
        if ($exception instanceof HttpException) {
            $code = $exception->getStatusCode();
            $message = Response::$statusTexts[$code];
            return $this->generateResponse($message, $code);
        }
        if ($exception instanceof ModelNotFoundException) {
            $model = strtolower(class_basename($exception->getModel()));
            return $this->generateResponse('Does not exist any instance of '.$model.' with the given id', Response::HTTP_NOT_FOUND);
        }
        if ($exception instanceof AuthorizationException) {
            return $this->generateResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }
        if ($exception instanceof AuthenticationException) {
            return $this->generateResponse($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
        if ($exception instanceof ValidationException) {
            $errors = $exception->validator->errors()->getMessages();
            return $this->generateResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($exception instanceof ClientException) {
            $message = $exception->getResponse()->getBody();
            $code = $exception->getCode();

            return $this->generateResponse($message, $code);
        }
        if ($exception instanceof RouteNotFoundException) {
            return $this->generateResponse('Route not found ('.$request->fullUrl().')', Response::HTTP_NOT_FOUND);
        }


        if (env('APP_DEBUG', false)) {
            return parent::render($request, $exception);
        }
        return $this->generateResponse('Unexpected error. Try later', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
