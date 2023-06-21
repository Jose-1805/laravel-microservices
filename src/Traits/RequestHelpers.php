<?php

namespace Jose1805\LaravelMicroservices\Traits;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator as ValidationValidator;
use Illuminate\Validation\Validator;

trait RequestHelpers
{
    // Almacena la información de los archivos procesados
    protected $files = [];
    /**
     * Crea una instancia de Validator a partir de los datos de una clase FormRequest
     *
     * Si recibe el parámetro $id, este se envía a la función rules() que se utiliza para
     * definir las reglas de validación del FormRequest. Por lo tanto agregue el parámetro
     * en la función rules de su FormRequest public function rules($id = null)
     *
     * @param string $form_request_class
     * @param array $data
     * @param string $id
     * @return Validator
     */
    public function formRequestToValidator(string $form_request_class, array $data, $id = null): Validator
    {
        $request = new ($form_request_class)();
        return ValidationValidator::make($data, $request->rules($id), $request->messages(), $request->attributes());
    }


    /**
     * Convierte archivos almacenados en ruta temporal de almacenamiento compartido en objetos UploadedFile
     *
     * El array recibido puede contener el siguiente formato
     * ["name" => "name.ext", "original_name" => "name.ext", "type"=>"type/type", "path" : "path"]
     * También puede recibir un array con hijos que contengan el formato anterior.
     *
     * Si no se recibe un array se regresa el mismo valor recibido
     *
     * @param array $data
     * @return mixed
     */
    public function convertToUploadedFile($data): mixed
    {
        if(!is_array($data)) {
            return $data;
        }
        // Determina si el array es asociativo, esto indica que contiene un sol archivo
        // Si no es asociativo es un array con varios archivos
        $isAssoc = function (array $arr): bool {
            if (array() === $arr) {
                return false;
            }
            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        // Realiza la conversión del array a un archivo
        $convert = function ($fileData): UploadedFile {
            $this->files[] = $fileData;
            $uploadedFile = new UploadedFile(
                storage_path('app/'.$fileData['path'].'/'.$fileData['name']),
                $fileData['original_name'],
                $fileData['type'],
                null,
                true
            );
            return $uploadedFile;
        };

        if($isAssoc($data)) {
            return $convert($data);
        } else {
            $result = [];
            for($i = 0; $i < count($data); $i++) {
                if(is_array($data[$i]) && $isAssoc($data[$i])) {
                    $result[] = $convert($data[$i]);
                }
            }
            return $result;
        }
    }

    /**
     * Elimina archivos almacenados en ruta temporal de almacenamiento compartido
     *
     * @param mixed $data
     * @return void
     */
    public function deleteTempFiles($data = null): void
    {
        $data = $data ?? $this->files;
        if(is_array($data)) {
            // Determina si el array es asociativo, esto indica que contiene un sol archivo
            // Si no es asociativo es un array con varios archivos
            $isAssoc = function (array $arr): bool {
                if (array() === $arr) {
                    return false;
                }
                return array_keys($arr) !== range(0, count($arr) - 1);
            };

            // Realiza la conversión del array a un archivo
            $delete = function ($fileData) {
                @unlink(storage_path('app/'.$fileData['path'].'/'.$fileData['name']));
                $file_system = new Filesystem();
                // Directorio queda vacío
                if($file_system->exists(storage_path('app/'.$fileData['path'])) && empty($file_system->files(storage_path('app/'.$fileData['path'])))) {
                    $file_system->deleteDirectory(storage_path('app/'.$fileData['path']));
                }
            };

            if($isAssoc($data)) {
                $delete($data);
            } else {
                for($i = 0; $i < count($data); $i++) {
                    if(is_array($data[$i]) && $isAssoc($data[$i])) {
                        $delete($data[$i]);
                    }
                }
            }
        }
    }
}
