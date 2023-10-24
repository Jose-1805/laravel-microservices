# LARAVEL MICROSERVICES

Este paquete esta desarrollado como una librería para crear api gateways o microservicios con laravel. Se enfoca en una arquitectura donde un api gateway es el único punto de entrada a una aplicación y también es el encargado de validar permisos, autenticación y gestión de usuarios. El api gateway puede comunicarse con los microservicios de manera directa a través de peticiones http o en segundo plano utilizando RabbitMq como servicio para comunicación entre el api gateway y los microservicios. Los servicios pueden comunicarse entre ellos únicamente a través del api gateway.

## INSTALACIÓN

Para instalar el paquete ejecute el siguiente comando de composer:

```
composer require jose-1805/laravel-microservices
```

## API GATEWAY

## Configuración api gateway

Para configurar su api gateway realice los siguientes pasos para establecer la configuración necesaria

### Paso #1

Publique el archivo de configuración inicial con uno de los siguientes comandos:

1. Si desea implementar modelos con el uso de UUIDS

```
php artisan vendor:publish --tag=laravel-microservices-config-api-gateway
```

2. Si desea que los modelos se implementen sin el uso de UUIDS

```
php artisan vendor:publish --tag=laravel-microservices-config-api-gateway-no-uuids
```

Se publicará un archivo de configuración llamado `laravel_microservices.php̣` en el directorio config de su proyecto y un archivo de migración de la tabla de usuarios cuyo nombre termina en `laravel_microservices_create_users_table.php`, agregue los campos adicionales que requiera en la tabla de usuarios y elimine la migración de la tabla de usuarios por defecto. A continuación se describen los valores del archivo de configuración.

`use_uuid`
Determina si se deben utilizar uuids en los modelos del paquete, no debe editar este valor y que se define automáticamente de acuerdo al comando ejecutado para publicar los archivos de configuración.

`background`
Este item contiene la configuración para resolver las solicitudes en segundo plano, dentro de él encontrará el key `events` que se encarga de asociar un evento (un evento es el nombre asignado a una solicitud en segundo plano) con un el nombre de la clase que se ejecutará al recibir dicho evento, este valor se debe ir diligenciando a medida que se crean eventos aunque en el api gateway no es común agregar eventos. Por lo general el api gateway recibirá únicamente el evento `background_request_result` que hace referencia al resultado de la ejecución de una solicitud en segundo plano. El paquete se encarga de esto y realiza los cambios necesarios en el registro en base de datos de la solicitud en segundo plano. Si desea cambiar esta clase asegúrese de registrar el resultado de la solicitud en la columna `output_data` de la tabla `background_requests` y también cambiar el estado como `1` en la columna `state`. En la sección de comandos para api gateway se explica como se crean los eventos y las clases asociadas.

`is_api_gateway`
Este valor le permite al paquete identificar en que modo debe establecer las configuraciones para un el correcto funcionamiento de todos los elementos de lo componen. Este valor no se debe editar.

`microservices`
Contiene un array con la configuración de los microservicios a los que se puede conectar el api gateway, a partir de esta configuración se irán creando los elementos necesarios para establecer una conexión con cada microservicio, esto se explica más adelante. Los valores de este array se pueden ir creando a medida que se desarrolla cada microservicio y el nombre de cada microservicio debe estar en `minúsculas`.

`roles`
Almacena un array de configuraciones de roles que se almacenan en la base de datos al ejecutar el comando `lm:sync-roles-and-permissions`, este comando almacena uno a uno todos los nombres de permisos encontrados en el item `permissions` y después crea o sincroniza los roles configurados junto al team definido y sus permisos.

```
php artisan lm:sync-roles-and-permissions
```

### Paso #2

Si desea trabajar con equipos para el manejo de roles y permisos publique el archivo de configuración de `laravel-permission` con el siguiente comando:

```
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag=permission-config
```

Se publicará un archivo de configuración llamado `permission.php` en el directorio `config` de su proyecto, establezca el valor de la clave `teams` en `true`.

### Paso #3

Configure las siguientes variables de entorno en su archivo `.env`

Configuración para conexión a rabbit mq

```
RABBITMQ_HOST
RABBITMQ_PORT
RABBITMQ_USER
RABBITMQ_PASSWORD
RABBITMQ_VHOST
```

Opcionalmente puede configurar también:

```
RABBITMQ_EXCHANGE               // Por defecto se asigna 'microservices.topic'
RABBITMQ_EXCHANGE_TYPE          // Por defecto se asigna 'topic'
RABBITMQ_INTERVAL_CONNECTION    // Por defecto se asigna 5
```

Este paquete contiene la instalación de `predis/predis` para la conexión a Redis, configure su archivo .env con las variables mínimas para la conexión con redis:

```
REDIS_HOST // Url del servidor o nombre del contenedor donde se encuentra instalado Redis
REDIS_PASSWORD // Contraseña de acceso al redis
REDIS_PORT // Puerto habilitado para la conexión con redis
REDIS_CLIENT // Cliente para manejo de conexiones, si no tiene instalada la extensión de phpredis puede utilizar predis/predis que es un paquete que no requiere la instalación de la extensión phpredis
```

Ahora que ha configurado la conexión a redis puede asignar redis como driver para cache, sesión y/o colas

```
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

Configure las variables de entorno de acceso a la base de datos

### Paso #4

Agregue a la lista de aliases del archivo `config/app.php` la siguiente configuración para la clase de administración de Rabbit Mq

```
'Amqp' => Bschmitt\Amqp\Facades\Amqp::class
```

### Paso #5

Agregue los middlewares de laravel permission en el archivo `app\Http\Kernel.php` en la variable `$middlewareAliases`

```
'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
```

### Paso #6

Si va a utilizar autenticación para un SPA debe habilitar o agregar el siguiente middleware en la clave api del archivo `app\Http\Kernel.php`

```
\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
```

### Paso #7

Agregar el trait `ApiResponser` y la función `renderExceptions` a la clase `app/Exceptions/Hanlder.php` para el manejo estandarizado de respuestas.
Antes de la declaración de la clase

```
use Jose1805\LaravelMicroservices\Traits\ApiResponser;
```

Después de la declaración de la clase

```
use ApiResponser;

public function render($request, Throwable $exception)
{
    return $this->renderExceptions($request, $exception);
}
```

### Paso #8

Configure su modelo `User` para que extienda de `Jose1805\LaravelMicroservices\Models\(User o UserUuid)`. Estas clases ya extienden del `Model` de laravel y utilizan implementaciones y traits requeridos para el funcionamiento correcto de este paquete.

### Paso #9

Si utiliza la configuración con uuids, en la migración de personal access tokens cambie `$table->morphs("tokenable");` por `$table->uuidMorphs("tokenable");`

### Paso #10

Ejecute las migraciones de base de datos con

```
php artisan migrate
```

### Paso #11

Puede agrupar todas sus rutas o las que desee con el middleware `teams` que se encarga de validar que las peticiones de un usuario autenticado contengan el header `Team-Id` para establecer el equipo asociado al usuario en la sesión actual

## Comandos para api gateway

Este paquete contiene algunos comandos artisan útiles dentro del proceso de desarrollo y la puesta en marcha del proyecto

### Roles y permisos

```
php artisan lm:sync-roles-and-permissions
```

Este comando sincroniza en la base de datos los roles y permisos configurados los campos `roles` y `permissions` del archivo de configuración del paquete `config/laravel_microservices.php`.

### Worker RabbitMQ

```
php artisan lm:consume-amqp queue-name
```

Este comando se conectará a la cola que indique en `queue-name` (para el api gateway generalmente se utiliza `api_gateway_queue.default`) y de acuerdo al evento recibido ejecutará el método handle de la clase asociada al evento en el archivo `config/background.php`. Si no existe el evento se registrará un mensaje en el log. En producción (si desea también en desarrollo) debe ejecutar este paquete en un administrador de procesos como `supervisord` con una configuración como la siguiente:

```
[program:amqp_consumer]
process_name=%(program_name)s_%(process_num)02d
command=php -d variables_order=EGPCS /var/www/html/artisan consume:amqp api_gateway_queue.default
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

### Tareas en segundo plano

```
php artisan lm:make-resolver NombreTarea --event=event-name
```

Ejecute este comando para crear y configurar una tarea para resolver en segundo plano, por lo general esto no se requiere en el api gateway ya que el evento `background_request_result` viene con una implementación definida en el paquete la cual actualiza la información de la solicitud en la base de datos (tabla `background_requests`).
El comando creará un archivo `app/Background/NombreTarea.php`, dentro de la clase incluida en el archivo encontrará un método `handle`, que recibe el nombre del evento y los datos de la petición en segundo plano, realice las tareas requeridas y retorne una respuesta que será enviada al servicio que solicitó la acción. El comando también configura el archivo `config/background.php` para que el evento recibido en `--event` se asocie a la clase `NombreTarea`

### Conexión a micro servicios

```
php artisan lm:sync-microservice-connections MicroserviceName1 MicroserviceName2 ... MicroserviceNameN
```

Este comando crea los recursos y configuraciones necesarias para establecer una conexión con uno o varios microservicios, puede pasar 0, 1 o más nombres de microservicios. También puede crear previamente el código base de los microservicios con la instalación del paquete `laravel-microservices` ya que se va a generar un access token que debe almacenar en el microservicio para autenticar peticiones en el api gateway.

Si envía nombres de microservicios debe asegurarse que estos estén configurados o por lo menos aparezcan en el archivo `config/laravel_microservices.php` en la clave `microservices`, si el nombre del microservicio no se encuentra en el archivo el comando no tomará ninguna acción. Si no se envían nombres de microservicios el comando creará los elementos necesarios para conectarse a todos los nombres de microservicios encontrados en el archivo.

Una vez que se ejecute el comando, se crean controladores, rutas de acceso, se almacenan los microservicios en la base de datos y se crea un token de acceso por cada uno de los nuevos microservicios nuevos, debe almacenar este token en sus respectivos microservicios para permitir las solicitudes desde el microservicio hacia el api gateway.

## Rutas de api gateway

El paquete incluye algunas rutas importantes para la ejecución del proyecto

### (POST) /api/token

Esta ruta se utiliza para obtener un token de acceso al sistema a través de las credenciales del usuario. Recibe como parámetros `email, password y device_name`. Si los datos son correctos la ruta retorna el token del usuario en la clave `token`, los datos básicos del usuario en la clave `user` y si activó el manejo de roles y permisos con equipos recibirá los identificadores de los equipos asociados al cliente en la clave `teams`, en cada solicitud debe enviar un header `Team-Id` con el valor del id del equipo que se debe utilizar para consultar la información del usuario.

### (POST) /api/logout

Esta ruta cierra la sesión del usuario eliminando el `token` que se envía en la solicitud.

### (GET) /api/background-request-result/{id}/{event}

Esta ruta permite la consulta del estado actual de una solicitud en segundo plano, recibe el id de la solicitud y el nombre del evento asociado. El usuario debe estar autenticado y solo puede consultar solicitudes que han sido registradas por él. Una vez que la tarea a finalizado se elimina automáticamente después de que se realiza la primera consulta de su nuevo estado.

### (GET) /api/user-data

Esta ruta permite la consulta de la información completa del usuario

## MICROSERVICES

## Configuración micro servicios

Para configurar un micro servicio realice los siguientes pasos para establecer la configuración necesaria

### Paso #1

Publique el archivo de configuración con el siguiente comando

```
php artisan vendor:publish --tag=laravel-microservices-config-microservice
```

Se publicará un archivo de configuración llamado `laravel_-_microservices.php` en el directorio `config` de su proyecto, por ahora no debe realizar ningún cambio en este archivo pero debe agregar las variables de entorno para la configuración de acceso al api gateway (`API_GATEWAY_PUBLIC_URL, API_GATEWAY_BASE_URI` y `API_GATEWAY_ACCESS_TOKEN`). A continuación, se describen las configuraciones que se pueden realizar con los elementos del array.

`background`
Este item contiene la configuración para resolver las solicitudes en segundo plano, dentro de él encontrará el key `events` que se encarga de asociar un evento (un evento es el nombre asignado a una solicitud en segundo plano) con un el nombre de la clase que se ejecutará al recibir dicho evento, este valor se debe ir diligenciando a medida que se crean eventos. En la sección de comandos para microservicios se explica como se crean los eventos y las clases asociadas.

`is_api_gateway`
Este valor le permite al paquete identificar en que modo debe establecer las configuraciones para un el correcto funcionamiento de todos los elementos de lo componen. Este valor no se debe editar.

`access_tokens`
Almacena los tokens de acceso para validar peticiones http realizadas desde el api gateway al microservicio, no es necesario editar este valor ni agregar la variable de entorno `ACCESS_TOKENS` al su archivo .env, más adelante se explica como se gestionan estos tokens con el comando `lm:make-access-token`.

`api_gateway`
Configuración de acceso al api gateway. En su archivo `.env` debe definir las variables de entorno `API_GATEWAY_PUBLIC_URL, API_GATEWAY_BASE_URI` y `API_GATEWAY_ACCESS_TOKEN`</br>

```
API_GATEWAY_PUBLIC_URL: Esta es la url pública del api gateway, se utiliza para agregarla en la paginación de los modelos.
```

```
API_GATEWAY_BASE_URI: Esta es la url que se utiliza para que los micro servicios creados realicen solicitudes a api gateway.
```

```
API_GATEWAY_ACCESS_TOKEN: Este es el token de acceso al api gateway desde el micro servicio, este token se obtiene en el api gateway cuando se registra el micro servicio en la base de datos.
```

### Paso #2

Configure las siguientes variables de entorno en su archivo `.env`

Configuración para conexión a rabbit mq

```
RABBITMQ_HOST
RABBITMQ_PORT
RABBITMQ_USER
RABBITMQ_PASSWORD
RABBITMQ_VHOST
```

Opcionalmente puede configurar también:

```
RABBITMQ_EXCHANGE               // Por defecto se asigna 'microservices.topic'
RABBITMQ_EXCHANGE_TYPE          // Por defecto se asigna 'topic'
RABBITMQ_INTERVAL_CONNECTION    // Por defecto se asigna 5
```

Configuración para tareas en segundo plano

```
BACKGROUND_EVENT_RESPONSE  // Por defecto se asigna 'background_request_result'
BACKGROUND_QUEUE_RESPONSE  // Por defecto se asigna 'api_gateway_queue.default'
```

Este paquete contiene la instalación de `predis/predis` para la conexión a Redis, configure su archivo .env con las variables mínimas para la conexión con redis:

```
REDIS_HOST // Url del servidor o nombre del contenedor donde se encuentra instalado Redis
REDIS_PASSWORD // Contraseña de acceso al redis
REDIS_PORT // Puerto habilitado para la conexión con redis
REDIS_CLIENT // Cliente para manejo de conexiones, si no tiene instalada la extensión de phpredis puede utilizar predis/predis que es un paquete que no requiere la instalación de la extensión phpredis
```

Ahora que ha configurado la conexión a redis puede asignar redis como driver para cache, sesión y/o colas

```
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

Configure las variables de entorno de acceso a la base de datos

### Paso #3

Agregue a la lista de aliases del archivo `config/app.php` la siguiente configuración para la clase de administración de Rabbit Mq

```
'Amqp' => Bschmitt\Amqp\Facades\Amqp::class
```

### Paso #4

Agregar el trait `ApiResponser` y la función renderExceptions a la clase `app/Exceptions/Hanlder.php` para el manejo estandarizado de respuestas
Antes de la clase

```
use Jose1805\LaravelMicroservices\Traits\ApiResponser;
```

Después de la clase

```
use ApiResponser;

public function render($request, Throwable $exception)
{
    return $this->renderExceptions($request, $exception);
}
```

### Paso #5

Agregue el midleware `\Jose1805\LaravelMicroservices\Http\Middleware\Service\AuthenticateAccessMiddleware::class` en la variable `$middleware` del archivo `app\Http\Kernel.php` para validar que todas las peticiones contengan autenticación desde el api_gateway. Si solo desea validar algunas peticiones agregue el middleware `auth_api_gateway` a las rutas que desea que tengan validación.

### Paso #6

Edite su modelo `User` con el siguiente código, si no utiliza uuids en el modelo user del api gateway elimine del siguiente código la línea que contiene `use HasUuids;`

```
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class User extends Model
{
    use HasUuids;
}
```

## Comandos para micro servicios

Este paquete contiene algunos comandos artisan útiles dentro del proceso de desarrollo y la puesta en marcha del proyecto

### Worker RabbitMQ

```
php artisan lm:consume-amqp queue-name
```

Este comando se conectará a la cola que indique en `queue-name` (el nombre se esta cola también se configura al crear el servicio en el api gateway) y de acuerdo al evento recibido ejecutará el método handle de la clase asociada al evento en el archivo `config/background.php`. Si no existe el evento se registrará un mensaje en el log. En producción (si desea también en desarrollo) debe ejecutar este paquete en un administrador de procesos como `supervisord` con una configuración como la siguiente:

```
[program:amqp_consumer]
process_name=%(program_name)s_%(process_num)02d
command=php -d variables_order=EGPCS /var/www/html/artisan consume:amqp service_name_queue.default
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

### Tokens de acceso

```
php artisan lm:make-access-token
```

Este comando crea un nuevo token de acceso al servicio y lo registra automáticamente en su archivo `.env` para validar peticiones http realizadas desde el api gateway. Después de generar este token lo debe agregar a la configruación del microservicio en el api gateway.

### Tareas en segundo plano

```
php artisan lm:make-resolver NombreTarea --event=event-name
```

Ejecute el siguiente comando para crear y configurar una tarea para resolver en segundo plano.
El comando creará un archivo `app/Background/NombreTarea.php`, dentro de la clase incluida en el archivo encontrará un método `handle`, que recibe el nombre del evento y los datos de la petición en segundo plano, realice las tareas requeridas y retorne una respuesta que será enviada al api gateway. El comando también configura el archivo `config/laravel_microservices.php` en el elemento `background` para que el evento recibido en `--event` se asocie a la clase `NombreTarea`.

### Creación de recursos del servicio

```
 php artisan lm:make-resource ResourceName --route=/example/example
```

Este comando se utiliza para crear un recurso con modelo, requests, controlador y rutas. El argumento `--route` no es obligatorio, si no lo envía el sistema asigna automáticamente la ruta de acuerdo al nombre del recurso, si el resultado no es el esperado puede cambiar esto directamente en su archivo de rutas.
