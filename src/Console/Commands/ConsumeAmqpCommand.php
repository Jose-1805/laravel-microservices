<?php

namespace Jose1805\LaravelMicroservices\Console\Commands;

use Jose1805\LaravelMicroservices\Background\src\BackgroundRequestResolver;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsumeAmqpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:consume-amqp {queue}';
    protected $connection_error = true;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea worker para consumir mensajes amqp en una cola';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        Log::info('Conectando a Rabbit MQ');
        while(!$this->connect()) {
            sleep(config('amqp.interval_connection', 5));
            if($this->connection_error) {
                Log::info('Intentando conectar nuevamente a RabbitMq');
            }
        }
    }

    public function connect()
    {
        $result = null;

        try {
            $result = \Amqp::consume($this->argument('queue'), function ($message, $resolver) {
                $background_request_resolver = new BackgroundRequestResolver($message->getRoutingKey(), $message->body);
                $background_request_resolver->resolve();
                $resolver->acknowledge($message);
            });

            if($this->connection_error) {
                $this->connection_error = false;
                Log::info('Conectado correctamente a Rabbit MQ');
            }
        } catch (Exception $e) {
            $result = null;
            $this->connection_error = true;
            Log::error('Error en conexión a Rabbit MQ', ['error' => $e->getMessage()]);
        }
        return $result;
    }
}
