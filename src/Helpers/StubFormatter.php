<?php

namespace App\Helpers;

use Illuminate\Filesystem\Filesystem;

class StubFormatter
{
    /**
     * Filesystem instance
     * @var Filesystem
     */
    protected $files;
    protected $final_path;
    protected $stub_vars;
    protected $stub_path;
    protected $start_separator;
    protected $end_separator;

    /**
     * Create a new command instance.
     * @param Filesystem $files
     */

    /**
     * Clase para generar archivos formateados con stubs
     *
     * @param Filesystem $files
     * @param string $final_path            Ruta absoluta con nombre de archivo donde se almacenará el resultado
     * @param array  $stub_vars             Array con datos requeridos en el archivo stub
     * @param string $stub_path             Ruta absoluta con nombre de archivo donde se encuentra el stub
     * @param string $start_separator       Caracteres iniciales que envuelven a las variables del archivo stub
     * @param string $end_separator         Caracteres finales que envuelven a las variables del archivo stub
     */
    public function __construct($final_path, $stub_vars, $stub_path, Filesystem $files)
    {
        $this->files = $files;
        $this->final_path = $final_path;
        $this->stub_vars = $stub_vars;
        $this->stub_path = $stub_path;
        $this->start_separator = '$';
        $this->end_separator = '$';
    }

    /**
     * Reasigna los valores de los separadores
     *
     * @param string $start
     * @param string $end
     * @return void
     */
    public function setSeparators($start, $end)
    {
        $this->start_separator = $start;
        $this->end_separator = $end;
    }

    /**
     * Crea un directorio para el archivo generado si es necesario
     *
     * @return string
     */
    protected function makeDirectory()
    {
        $dir = dirname($this->final_path);
        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0777, true, true);
        }

        return $dir;
    }


    /**
     * Obtiene el contenido formateado del stub
     *
     * @return bool|mixed|string
     *
     */
    public function getSourceFile()
    {
        $contents = file_get_contents($this->stub_path);

        foreach ($this->stub_vars as $search => $replace) {
            $contents = str_replace($this->start_separator.$search.$this->end_separator, $replace, $contents);
        }

        return $contents;
    }

    public function make()
    {
        $this->makeDirectory();
        $content = $this->getSourceFile();
        $this->files->put($this->final_path, $content);
    }
}
