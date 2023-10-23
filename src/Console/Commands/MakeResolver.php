<?php

namespace Jose1805\LaravelMicroservices\Console\Commands;

use Jose1805\LaravelMicroservices\Helpers\StubFormatter;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Pluralizer;

class MakeResolver extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:make-resolver {name} {--E|event=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea una clase para solucionar una petición en segundo plano';

    /**
     * Filesystem instance
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path_resolver = base_path('app/Background') . '/' . $this->getClassName($this->argument('name')) . '.php';
        if(file_exists($path_resolver)) {
            $this->warn('Ya existe un archivo llamado ' . $this->getClassName($this->argument('name')) . '.php');
        } else {
            $this->comment('Creando archivo ...');
            $this->comment($path_resolver . ' ...');

            $path_stub_resolver = __DIR__ . '/../../../stubs/' . (config('microservices.is_api_gateway') ? 'ApiGateway' : 'Service') . '/resolver-resource.stub';
            $formatter_resolver = new StubFormatter(
                $path_resolver,
                $this->getStubVariables(),
                $path_stub_resolver,
                $this->files
            );
            $formatter_resolver->make();
            $this->info('Archivo creado con éxito');

            if($this->option('event')) {
                $this->addEventSetting($this->option('event'));
            }

            $this->info('Recurso creado con éxito');
            if(!$this->option('event')) {
                $this->comment('Recuerde configurar el evento asociado en el archivo de configuración config/background.php');
            }
        }
    }

    /**
     * Map the stub variables present in stub to its value
     *
     * @return array
     *
     */
    public function getStubVariables()
    {
        return [
            'CLASS_NAME' => $this->getClassName($this->argument('name')),
            'CODE' => $this->getCodeExample($this->option('event'))
        ];
    }


    /**
     * Nombre para asignar la clase
     * @param $name
     * @return string
     */
    public function getClassName($name)
    {
        return ucwords(Pluralizer::singular(Str::of($name)->camel()));
    }

    /**
     * Agrega la configuración de evento requerida
     *
     * @return void
     */
    public function addEventSetting($event)
    {
        $file = fopen(base_path('config/laravel_microservices.php'), 'r+') or die('Error');

        $events_found = false;
        $event_is_added = false;

        $content = '';
        while ($line = fgets($file)) {
            // Se encuentra por primera vez la clave 'events'
            if (str_contains($line, '\'events\' => [') && !$events_found) {
                $events_found = true;
            } elseif ($events_found && str_contains($line, '],') && !$event_is_added) {
                $content .= '        \'' . $event . '\' => App\\Background\\' . $this->getClassName($this->argument('name')) . '::class,' . PHP_EOL;
                $event_is_added = true;
            }
            $content .= $line;
        }
        rewind($file);
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * Código de ejemplo base de acuerdo al evento
     *
     * @param string|null $event
     * @return string
     */
    public function getCodeExample($event = null): string
    {
        $event = strtolower($event);
        $code = 'Su código aquí';
        if($event) {
            if(str_contains($event, 'create') || str_contains($event, 'store')) {
                $code = '
        /*
        //Si se envían archivos se hace la conversión
        if(array_key_exists("param_name", $this->input_data)) {
            $this->input_data["param_name"] = $this->convertToUploadedFile($this->input_data["param_name"]);
        }

        $validator = $this->formRequestToValidator(StoreModelRequest::class, $this->input_data);
        $errors = $validator->errors();

        $response = [];

        // La validación no pasa
        if($errors->count()) {
            $response = [\'data\' => $errors, \'code\' => Response::HTTP_UNPROCESSABLE_ENTITY];
        } else {
            $model = Model::create($this->input_data);
            $response = [\'data\' => $model, \'code\' => Response::HTTP_CREATED];
        }

        //Se eliminan archivos temporales convertidos a UploadedFile
        $this->deleteTempFiles();
        */
                ';
            } elseif(str_contains($event, 'update') || str_contains($event, 'upgrade')) {
                $code = '
        /*$response = [];

        if(!array_key_exists(\'id\', $this->input_data)) {
            $response = [\'data\' => [\'Elemento no encontrado\'], \'code\' => Response::HTTP_NOT_FOUND];
        } else {
            $model = Model::find($this->input_data[\'id\']);

            if(!$model) {
                $response = [\'data\' => [\'Elemento no encontrado\'], \'code\' => Response::HTTP_NOT_FOUND];
            } else {

                //Si se envían archivos se hace la conversión
                if(array_key_exists("param_name", $this->input_data)) {
                    $this->input_data["param_name"] = $this->convertToUploadedFile($this->input_data["param_name"]);
                }

                $validator = $this->formRequestToValidator(UpdateModelRequest::class, $this->input_data, $model->id);
                $errors = $validator->errors();


                // La validación no pasa
                if($errors->count()) {
                    $response = [\'data\' => $errors, \'code\' => Response::HTTP_UNPROCESSABLE_ENTITY];
                } else {
                    $model->update($this->input_data);
                    $response = [\'data\' => $model, \'code\' => Response::HTTP_OK];
                }

                //Se eliminan archivos temporales convertidos a UploadedFile
                $this->deleteTempFiles();
            }
        }*/
                ';
            } elseif(str_contains($event, 'delete') || str_contains($event, 'destroy')) {
                $code = '
        /*$response = [];

        if(!array_key_exists("id", $this->input_data)) {
            $response = ["data" => ["Elemento no encontrado"], "code" => Response::HTTP_NOT_FOUND];
        } else {
            $model = Model::find($this->input_data["id"]);

            if(!$model) {
                $response = ["data" => ["Elemento no encontrado"], "code" => Response::HTTP_NOT_FOUND];
            } else {
                $model->delete();
                $response = ["data" => $model, "code" => Response::HTTP_OK];
            }
        }*/
                ';
            }
        }
        return $code;
    }
}
