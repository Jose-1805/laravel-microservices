<?php

namespace Jose1805\LaravelMicroservices\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

trait JsonRequestConverter
{
    // Nombre de directorio donde se almacenan los archivos temporales compartidos
    protected $shared_temps_dir = 'shared_temps';
    /**
     * Convierte los datos de un request en un Json
     *
     * @param Request $request
     */
    public function requestToJson(Request $request): string
    {
        $requestData = $request->all();

        // Se verifica si el request contiene archivos
        foreach ($request->allFiles() as $fieldName => $files) {
            $fileData = null;
            if(is_a($files, UploadedFile::class)) {
                $fileData = $this->getTempFileData($files);
            } elseif(gettype($files) == 'array') {
                $fileData = [];
                foreach ($files as $file) {
                    $fileData[] = $this->getTempFileData($file);
                }
            }
            $requestData[$fieldName] = $fileData;
        }

        // Convert the array to a JSON string and return it
        return json_encode($requestData);
    }

    /**
     * Obtiene la información de almacenamiento temporal de un archivo
     *
     * @param UploadedFile $file
     * @param bool $file
     */
    public function getTempFileData(UploadedFile $file): array
    {
        $name = $file->hashName();
        $original_name = $file->getClientOriginalName();
        $path = $this->shared_temps_dir.'/'.strtotime(date('Y-m-d H:i:s'));
        $file->storeAs($path, $name, 'local');
        return [
            'name' => $name,
            'original_name' => $original_name,
            'type' => $file->getMimeType(),
            'path' => $path
        ];
    }
}
