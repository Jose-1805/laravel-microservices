<?php

namespace Jose1805\LaravelMicroservices\Models;

use Jose1805\LaravelMicroservices\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BackgroundRequest extends Model
{
    use HasUuids;

    protected $table = 'background_requests';

    protected $fillable = ['event', 'state', 'input_data', 'output_data', 'user_id'];

    protected $casts = [
        'input_data' => 'json',
        'output_data' => 'json',
    ];

    /**
     * Relación al usuario asociado a la solicitud
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Publica la solicitud en segundo plano para que sea consumida por el servicio
     *
     * @param [string] $event
     * @param [string] $queue
     * @return void
     */
    public function publish($event, $queue): void
    {
        if($queue) {
            \Amqp::publish($event, json_encode($this->toArray()), ['queue' => $queue]);
        }
    }

    /**
     * Consulta el estado actual de una solicitud en segundo plano
     *
     * @param [type] $id
     * @param [type] $event
     * @param [type] $user_id
     * @return mixed
     */
    public static function result($id, $event, $user_id): mixed
    {
        $responser = (new class () {
            use ApiResponser;
        });

        $background_request = self::where('id', $id)
            ->where('event', $event)
            ->where('user_id', $user_id)
            ->first();

        if($background_request) {
            $data = [
                'state' => $background_request->state,
                'output_data' => $background_request->output_data
            ];

            // Las solicitudes finalizadas se eliminan una vez se consultan
            if($background_request->state == 1) {
                $background_request->delete();
            }

            return $responser->httpOkResponse($data);
        }
        return $responser->generateResponse('Not found', 404);
    }
}
