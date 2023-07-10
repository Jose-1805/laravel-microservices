<?php

namespace Jose1805\LaravelMicroservices\Console\Commands\ApiGateway;

use Jose1805\LaravelMicroservices\Helpers\StubFormatter;
use Jose1805\LaravelMicroservices\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Pluralizer;

class ServiceConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lm:make-service-connection {name} {base_uri} {access_token} {--P|path=} {--Q|queue=} {--only_seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear recursos y configuraciones necesarias para establecer una conexión a un servicio';

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
        // Ruta de almacenamiento del seeder con nombre completo
        $path_seeder = base_path('database/seeders/services') .'/' .$this->getClassName($this->argument('name')) . 'Seeder.php';

        if($this->option('only_seeder')) {
            if(Service::where('name', strtolower(Str::of($this->argument('name'))->snake()->value))->count()) {
                $this->error('Ya existe un servicio o controlador con el nombre sugerido \''.$this->argument('name').'\'');
            } else {
                $this->makeSeeder($path_seeder);
            }
        } else {
            // Ruta de almacenamiento del servicio con nombre completo
            $path_service = base_path('app/Services').'/'.$this->getClassName($this->argument('name')) . 'Service.php';
            // Ruta de almacenamiento del controlador con nombre completo
            $path_controller = base_path('app/Http/Controllers') .'/' .$this->getClassName($this->argument('name')) . 'Controller.php';

            // No existe ningún servicio o controlador con el nombre solicitado
            if (!$this->files->exists($path_service) && !$this->files->exists($path_controller) && !Service::where('name', strtolower(Str::of($this->argument('name'))->snake()->value))->count()) {
                $this->makeController($path_controller);

                $this->addRoute($this->getClassName($this->argument('name')) . 'Controller', $this->getRoute());

                $this->makeSeeder($path_seeder);
            } else {
                $this->error('Ya existe un servicio o controlador con el nombre sugerido \''.$this->argument('name').'\'');
            }
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
     * Nombre formateado del servicio
     *
     * @param string $name
     * @return string
     */
    public function getServiceName($name): string
    {
        return Str::of($name)->snake()->value.'_service';
    }

    /**
     * Obtiene la ruta base que se asigna a las rutas del asociadas al controlador generado
     *
     * @return string
     */
    public function getRoute(): string
    {
        $route = $this->getPath();
        return str_replace('/api/', '', $route);
    }

    /**
     * Obtiene el path para agregar a la ruta base y acceder a las funciones del servicio
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->option('path')) {
            return $this->option('path');
        } else {
            return '/api/'.Str::slug(Str::snake($this->getClassName($this->argument('name'))));
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
            'SERVICE_NAME' => $this->getServiceName($this->argument('name')),
            'CLASS_NAME_PLURAL' => Pluralizer::plural($this->getClassName($this->argument('name'))) ,
            'BASE_URI' => $this->argument('base_uri'),
            'PATH' => $this->getPath(),
            'SERVICE_NAME_SNAKE' => Str::of($this->argument('name'))->snake()->value,
            'ACCESS_TOKEN' => $this->argument('access_token'),
            'QUEUE' => $this->getQueueName(),
        ];
    }

    /**
     * Agrega las rutas de api para el controlador creado
     *
     * @return void
     */
    public function addRoute($controller_name, $route_name)
    {
        $this->comment('Agregando rutas ...');
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
        $this->info('Rutas agregadas con éxito');
    }

    public function makeController($path_controller)
    {
        $this->comment('Creando controlador ...');
        $path_stub_controller = __DIR__ . '/../../../../stubs/ApiGateway/service-controller.stub';
        $formatter_controller = new StubFormatter(
            $path_controller,
            $this->getStubVariables(),
            $path_stub_controller,
            $this->files
        );
        $formatter_controller->make();
        $this->info('Controlador creado con éxito');
    }

    /**
     * Crea un seeder para almacenar el servicio en la base de datos
     *
     * @return void
     */
    public function makeSeeder($path_seeder)
    {
        $this->comment('Creando seeder ...');
        $path_stub_seeder = __DIR__ . '/../../../../stubs/ApiGateway/service-seeder.stub';
        $formatter_controller = new StubFormatter(
            $path_seeder,
            $this->getStubVariables(),
            $path_stub_seeder,
            $this->files
        );
        $formatter_controller->make();
        $this->addSeederToAllServices();
        $this->info('Seeder creado con éxito'.PHP_EOL);
        $this->info('Para ejecutar el seeder y agregar a la base de datos ejecuta uno de los siguientes comandos:'.PHP_EOL);
        $this->info('*  Para agregar solo este servicio');
        $this->info('php artisan db:seed --class=Database\\\\Seeders\\\\services\\\\'.$this->getClassName($this->argument('name')).'Seeder');
        $this->info('*  Para agregar todos los servicios creados');
        $this->info('php artisan db:seed --class=Database\\\\Seeders\\\\services\\\\AllServices');
    }

    /**
     * Agrega el seeder creado a un nuevo seeder encargado de ejecutar todos los seeders
     *
     * @return void
     */
    public function addSeederToAllServices()
    {
        $file = fopen(base_path('database/seeders/services/AllServices.php'), 'r+') or die('Error');
        $is_added = false;
        $content = '';
        while ($line = fgets($file)) {
            if (str_contains($line, $this->getClassName($this->argument('name')).'Seeder::class,') && !$is_added) {
                $is_added = true;
            }

            if (str_contains($line, ']);') && !$is_added) {
                $content .= '           '.$this->getClassName($this->argument('name')).'Seeder::class,'.PHP_EOL;
                $is_added = true;
            }
            $content .= $line;
        }
        rewind($file);
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * Obtiene el nombre de la cola a la cual se conecta el servicio
     *
     * @return void
     */
    public function getQueueName()
    {
        return $this->option('queue') ? $this->option('queue') : Str::of($this->argument('name'))->snake()->value.'_queue.default';
    }
}
