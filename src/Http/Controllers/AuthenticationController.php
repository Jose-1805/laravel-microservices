<?php

namespace Jose1805\LaravelMicroservices\Http\Controllers;

use Jose1805\LaravelMicroservices\Models\User;
use Jose1805\LaravelMicroservices\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Jose1805\LaravelMicroservices\Traits\Teams;

class AuthenticationController extends Controller
{
    use ApiResponser;
    use Teams;

    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['logoutToken', 'authUserData']);
    }

    /**
     * Cierra la sesión de un usuario eliminando el token de acceso con el cual se loguea
     *
     * @param Request $request
     */
    public function logoutToken(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->httpOkResponse();
    }

    /**
     * Genera un token de acceso a partir de un correo y contraseña
     *
     * @param Request $request
     */
    public function token(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->generateResponse([
                    'email' => ['Los datos de acceso con incorrectos.'],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = ['token' => $user->createToken($request->device_name)->plainTextToken, 'user' => $user];

        if(config('permission.teams')) {
            $response['teams'] = $this->getTeams($user->id);
        }

        return $this->httpOkResponse($response);
    }

    /**
     * Datos del usuario autenticado
     *
     * @param Request $request
     */
    public function authUserData(Request $request): JsonResponse
    {
        return $this->httpOkResponse($request->user()->allData());
    }
}
