<?php

namespace Jose1805\LaravelMicroservices\Console\Commands\Service;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:make-access-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un nuevo token de acceso al servicio';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->comment('Agregando token ...');
        $result = $this->addAccessToken();
        if($result) {
            $this->comment('Token agregado con éxito, lo debe almacenar en un lugar seguro en el api gateway');
            $this->info($result);
        } else {
            $this->comment('Tarea no ejecutada con éxito');
        }
    }

    /**
     * Agrega el token de acceso al archivo .env
     *
     * @return void
     */
    public function addAccessToken()
    {
        $file_name = '.env';
        if(!file_exists(base_path($file_name))) {
            $this->error('No existe el archivo ' . $file_name);
            return false;
        } else {
            $token = Str::random(rand(30, 40));
            $file = fopen(base_path($file_name), 'r+') or die('Error');
            $content = '';
            $is_added = false;
            while ($line = fgets($file)) {
                //Ya existe el key ACCESS_TOKENS y no se a añadido el nuevo token
                if (str_contains($line, 'ACCESS_TOKENS=') && !$is_added) {
                    //Si hay más de un token separa por coma
                    $separator = strlen(trim($line)) > 14 ? ',' : '';
                    $line = str_replace('ACCESS_TOKENS=', 'ACCESS_TOKENS=' . $token . $separator, $line);
                    $is_added = true;
                }
                $content .= $line;
            }

            if(!$is_added) {
                $content .= 'ACCESS_TOKENS=' . $token . PHP_EOL;
            }
            rewind($file);
            fwrite($file, $content);
            fclose($file);
            return $token;
        }
    }
}
