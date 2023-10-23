<?php

namespace Jose1805\LaravelMicroservices\Console\Commands\ApiGateway;

use Jose1805\LaravelMicroservices\Helpers\StubFormatter;
use Jose1805\LaravelMicroservices\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Pluralizer;

class SyncMicroserviceConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:sync-microservice-connections {names?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear recursos y configuraciones necesarias para establecer una conexión a uno o varios microservicios';

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
        $microserviceNames = $this->argument('names') && count($this->argument('names')) ? $this->argument('names') : array_keys(config('laravel_microservices.microservices') ?? []);
        $results = [];
        foreach ($microserviceNames as $microserviceName) {
            $microserviceName = strtolower($microserviceName);
            // Existe información del microservicio en la configuración del paquete
            if(config('laravel_microservices.microservices')[$microserviceName] ?? false) {
                if(strlen($microserviceName) <= 100) {
                    $microserviceObject = Service::firstOrCreate(['name' => $microserviceName]);

                    // Ruta de almacenamiento del controlador con nombre completo
                    $pathController = base_path('app/Http/Controllers') . '/' . $this->getClassName($microserviceName) . 'Controller.php';

                    // No existe el controlador
                    if (!$this->files->exists($pathController)) {
                        $this->makeController($pathController, $microserviceName);
                        $this->addRoute($this->getClassName($microserviceName) . 'Controller', $this->getRoute($microserviceName), $microserviceName);

                        $results[$microserviceName] = [
                            'estado' => 'completo',
                            'respuesta' => $microserviceObject->createToken('services')->plainTextToken,
                        ];
                    } else {
                        $results[$microserviceName] = [
                            'estado' => 'completo',
                            'respuesta' => 'Microservicio configurado previamente, no se realizaron cambios',
                        ];
                    }

                } else {
                    $results[$microserviceName] = [
                        'estado' => 'falló',
                        'respuesta' => 'Nombre de microservicio demasiado largo',
                    ];
                }

            } else {
                $results[$microserviceName] = [
                    'estado' => 'falló',
                    'respuesta' => 'No se encontró la configuración del microservicios en config/laravel_microservices.php',
                ];
            }
        }

        $this->info('');
        $this->info('RESULTADOS ************************************************');
        $this->info('');
        foreach($results as $microserviceName => $value) {
            $this->info($microserviceName . '(' . $value['estado'] . ')');
            $this->info($value['respuesta']);
            $this->info('');
        }
    }

    /**
     * Nombre para asignar la clase del servicio
     * @param $name
     * @return string
     */
    public function getClassName($name)
    {
        return ucwords(Pluralizer::singular(Str::of($name)->camel()));
    }

    /**
     * Obtiene la ruta base que se asigna a las rutas del asociadas al controlador generado
     *
     * @return string
     */
    public function getRoute($microserviceName): string
    {
        $route = $this->getPath($microserviceName);
        return str_replace('/api/', '', $route);
    }

    /**
     * Obtiene el path para agregar a la ruta base y acceder a las funciones del servicio
     *
     * @return string
     */
    public function getPath($microserviceName): string
    {
        return '/api/' . Str::slug(Str::snake($this->getClassName($microserviceName)));
    }

    /**
     * Map the stub variables present in stub to its value
     *
     * @return array
     *
     */
    public function getStubVariables($microserviceName)
    {
        return [
            'CLASS_NAME' => $this->getClassName($microserviceName),
            'MICROSERVICE_NAME' => $microserviceName,
        ];
    }

    /**
     * Agrega las rutas de api para el controlador creado
     *
     * @return void
     */
    public function addRoute($controllerName, $routeName, $microserviceName)
    {
        $this->comment('Agregando rutas para ' . $microserviceName);
        $file = fopen(base_path('routes/api.php'), 'r+') or die('Error');
        $use_is_added = false;
        $content = '';
        while ($line = fgets($file)) {
            if (str_contains($line, 'use ') && !$use_is_added) {
                $content .= 'use App\Http\Controllers\\' . $controllerName . ';' . PHP_EOL;
                $use_is_added = true;
            }
            $content .= $line;
        }
        $content .= 'Route::apiResource(\'' . $routeName . '\', ' . $controllerName . '::class);' . PHP_EOL;
        rewind($file);
        fwrite($file, $content);
        fclose($file);
        $this->info('Rutas agregadas con éxito');
    }

    public function makeController($pathController, $microserviceName)
    {
        $this->comment('Creando controlador para ' . $microserviceName);
        $path_stub_controller = __DIR__ . '/../../../../stubs/ApiGateway/service-controller.stub';
        $formatter_controller = new StubFormatter(
            $pathController,
            $this->getStubVariables($microserviceName),
            $path_stub_controller,
            $this->files
        );
        $formatter_controller->make();
        $this->info('Controlador creado con éxito');
    }
}
