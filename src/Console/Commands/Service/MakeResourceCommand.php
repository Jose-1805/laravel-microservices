<?php

namespace Jose1805\LaravelMicroservices\Console\Commands\Service;

use Jose1805\LaravelMicroservices\Helpers\StubFormatter;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Pluralizer;

class MakeResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:make-resource {name} {--R|route=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea los recursos necesarios para un servicio';

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
        $path_controller = base_path('app/Http/Controllers') .'/' .$this->getClassName($this->argument('name')) . 'Controller.php';

        $this->comment('Creando recursos iniciales con artisan');
        Artisan::call('make:model '.$this->getClassName($this->argument('name')).' -m -c -R --api');
        @unlink($path_controller);
        $this->info('Recursos iniciales creados con éxito');


        $this->comment('Creando controlador ...');
        $path_stub_controller = __DIR__ . '/../../../../stubs/Service/controller-resource.stub';
        $formatter_controller = new StubFormatter(
            $path_controller,
            $this->getStubVariables(),
            $path_stub_controller,
            $this->files
        );
        $formatter_controller->make();
        $this->info('Controlador creado con éxito');


        $this->comment('Agregando rutas ...');
        $this->addRoute($this->getClassName($this->argument('name')) . 'Controller', $this->getRoute());
        $this->info('Rutas agregadas con éxito');
        $this->comment('*************************************************');
        $this->info('Proceso terminado, para utilizar la paginación de modelos debe agregar los array public $sort_columns y public $search_colums al modelo creado o al controlador.');
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
            'RESOURCE_NAME' => $this->getResourceName($this->argument('name'))
        ];
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
     * Nombre para asignar la clase del servicio
     * @param $name
     * @return string
     */
    public function getResourceName($name)
    {
        return Str::of($name)->snake()->value;
    }

    /**
     * Obtiene la ruta base que se asigna a las rutas del asociadas al controlador generado
     *
     * @return string
     */
    public function getRoute(): string
    {
        if ($this->option('route')) {
            return $this->option('route');
        } else {
            return Str::slug($this->getResourceName($this->argument('name')));
        }
    }

    /**
     * Agrega las rutas de api para el controlador creado
     *
     * @return void
     */
    public function addRoute($controller_name, $route_name)
    {
        $file = fopen(base_path('routes/api.php'), 'r+') or die('Error');
        $use_is_added = false;
        $content = '';
        while ($line = fgets($file)) {
            if (str_contains($line, 'use ') && !$use_is_added) {
                $content .= 'use App\Http\Controllers\\'.$controller_name.';'.PHP_EOL;
                $use_is_added = true;
            }
            $content .= $line;
        }
        $content .= 'Route::apiResource(\''.$route_name.'\', '.$controller_name.'::class);'.PHP_EOL;
        rewind($file);
        fwrite($file, $content);
        fclose($file);
    }
}
