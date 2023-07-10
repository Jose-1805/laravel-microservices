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

Publique el archivo de configuración inicial con el siguiente comando:

```
php artisan vendor:publish --tag=start-config-laravel-microservices
```

Se publicará un archivo de configuración llamado `microservices.php̣` en el directorio config de su proyecto, establezca el valor de la clave `is_api_gateway` en `true`. El paquete utiliza uuids por defecto para configurar la librería de `laravel-permission` y el modelo User, si desea utilizar identificadores enteros auto incrementables configure el valor de la clave `use_uuid` en `false`.

### Paso #2

Si desea trabajar con equipos para el manejo de roles y permisos publique el archivo de configuración de `laravel-permission` con el siguiente comando:

```
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag=permission-config
```

Se publicará un archivo de configuración llamado `permission.php` en el directorio `config` de su proyecto, establezca el valor de la clave `teams` en `true`.

### Paso #3

Con la configuración realizada, ahora puede publicar los archivos de configuración general del paquete con el siguiente comando:

```
php artisan vendor:publish --tag=laravel-microservices-config
```

Se publicarán los siguientes archivos en su proyecto:

`config/background.php`
Este archivo contiene la configuración para resolver las solicitudes en segundo plano, dentro de él encontrará inicialmente el key `events` que se encarga de asociar un evento (un evento es el nombre asignado a una solicitud en segundo plano) con un el nombre de la clase que se ejecutará al recibir dicho evento. Por lo general el api gateway recibirá únicamente el evento `background_request_result` que hace referencia al resultado de la ejecución de una solicitud en segundo plano. El paquete se encarga de esto y realiza los cambios necesarios en el registro en base de datos de la solicitud en segundo plano. Si desea cambiar esta clase asegurese de registrar el resultado de la solicitud en la columna `output_data` de la tabla `background_requests` y también cambiar el estado como `1` en la columna `state`. En la sección de comandos para api gateway se explica como se crean los eventos y las clases asociadas.

`database/migrations/X_X_X_XXX_laravel_microservices_create_users_table.php`
Este archivo contiene la migración de base de datos para la tabla de usuarios, debe eliminar la migración que ya existía para la tabla de usuarios y modificar la migración generada por este paquete de acuerdo a sus necesidades pero sin eliminar o modificar nada de lo que ya existe en el archivo.

`database/seeders/services/AllServices.php`
Este archivo llamará a los seeders que vaya creando poco a poco con la configuración de sus microservicios, se crea con el fin de que pueda crear todos sus servicios si se eliminan de su base de datos. Cada vez que crea un servicio con el comando `lm:make-service-connection` se crea un seeder para su servicio y se agrega en el seeeder `AllServices.php` para crear todos los servicios debe ejecutar el comando `php artisan db:seed --class=Database\Seeders\services\AllServices`.

`database/seeders/RolesAndPermissionsSeeder.php`
Este archivo contiene la configuración para registrar sus roles y permisos iniciales, realice los cambios necesarios de acuerdo a sus necesidades.

-   Establezca los permisos de su aplicación en la variable `$permissions`, tal cual como está en el ejemplo
-   Elimine, agregue o actualice las funciones de agregar roles y registrelas en la función `run()` de la clase

`database/seeders/UserAdmin.php`
Este archivo contiene la configuración para registrar su primer usuario Super Administrador, realice los cambios necesarios de acuerdo a sus necesidades y la tabla de usuarios.

Agregue las clases `RolesAndPermissionsSeeder` y `UserAdmin` al archivo `DatabaseSeeder.php` en el llamado a la función `call` si desea registrar sus roles y super administrador al ejecutar `php artisan db:seed`

### Paso #4

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

### Paso #5

Agregue a la lista de aliases del archivo `config/app.php` la siguiente configuración para la clase de administración de Rabbit Mq

```
'Amqp' => Bschmitt\Amqp\Facades\Amqp::class
```

### Paso #6

Agregue los middlewares de laravel permission en el archivo `app\Http\Kernel.php` en la variable `$middlewareAliases`

```
'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
```

### Paso #7

Si va a utilizar autenticación para un SPA debe habilitar o agregar el siguiente middleware en la clave api del archivo `app\Http\Kernel.php`

```
\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
```

### Paso #8

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

### Paso #9

Configure su modelo `User` para que extienda de `Jose1805\LaravelMicroservices\Models\(User o UserUuid)`. Estas clases ya extienden del `Model` de laravel y utilizan implementaciones y traits requeridos para el funcionamiento correcto de este paquete.

### Paso #10

Si utiliza la configuración con uuids, en la migración de personal access tokens cambie `$table->morphs("tokenable");` por `$table->uuidMorphs("tokenable");`

### Paso #11

Ejecute las migraciones de base de datos con

```
php artisan migrate
```

### Paso #12

Ejecute los seeders que para agregar sus roles, permisos y super administrador con

```
php artisan db:seed
```

### Paso #13

Puede agrupar todas sus rutas o las que desee con el middleware `teams` que se encarga de validar que las peticiones de un usuario autenticado contengan el header `Team-Id` para establecer el equipo asociado al usuario en la sesión actual

## Comandos para api gateway

Este paquete contiene algunos comandos artisan útiles dentro del proceso de desarrollo y la puesta en marcha del proyecto

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
php artisan lm:make-resolver NombreTarea event=event-name
```

Ejecute este comando para crear y configurar una tarea para resolver en segundo plano, por lo general esto no se requiere en el api gateway ya que el evento `background_request_result` viene con una implementación definida en el paquete la cual actualiza la información de la solicitud en la base de datos (tabla `background_requests`).
El comando creará un archivo `app/Background/NombreTarea.php`, dentro de la clase incluida en el archivo encontrará un método `handle`, que recibe el nombre del evento y los datos de la petición en segundo plano, realice las tareas requeridas y retorne una respuesta que será enviada al servicio que solicitó la acción. El comando también configura el archivo `config/background.php` para que el evento recibido en `--event` se asocie a la clase `NombreTarea`

### Conexión a micro servicios

```
php artisan lm:make-service-connection
```

Este comando crea los recursos y configuraciones necesarias para establecer una conexión con un microservicio, para ejecutar el comando tenga en cuenta lo siguiente:

-   Primero cree el código base del servicio con la instalación del paquete `laravel-microservices` para obtener un `access_token` para las solicitudes desde al `api_gateway` al servicio, el access token se requiere en los siguientes pasos.
-   Ejecute el comando `php artisan lm:make-service-connection Name BaseUri AccessToken`, el comando creará todos los recursos necesarios para conectarse al servicio `Name`, el comando crea registros de configuración de acuerdo al nombre asignado, para personalizar las variables puede ejecutar el comando con los parámetros opcionales como se muestra a en el siguiente ejemplo:

```
php artisan lm:make-service-connection Contact http://contact_service access_token_1sdsd1f2sdf1 --path=/api/contact --queue=contact_service_queue
```

Una vez que se ejecute el comando, se registrará un seed en su directorio `database/seeders/service` y las instrucciones de ejecución del seed. Cuando ejecute el seed su servicio se almacenará en la base de datos y se crea un token de acceso que tendrá que almacenar en su micro servicio para permitir las solicitudes desde el micro servicio hacia el api gateway.

## Rutas de api gateway

El paquete incluye cuatro rutas importantes para la ejecución del proyecto

### (POST) /api/token

Esta ruta se utiliza para obtener un token de acceso al sistema a través de las credenciales del usuario. Recibe como parámetros `email, password y device_name`. Si los datos son correctos la ruta retorna el token del usuario en la clave `token`, los datos básicos del usuario en la clave `user` y si activó el manejo de roles y permisos con equipos recibirá los identificadores de los equipos asociados al cliente en la clave `teams`. en cada solicitud debe enviar un header `Team-Id` con el valor del id del equipo que se debe utilizar para consultar la información del usuario.

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

Publique el archivo de configuración inicial con el siguiente comando

```
php artisan vendor:publish --tag=start-config-laravel-microservices
```

Se publicará un archivo de configuración llamado `microservices.php` en el directorio `config` de su proyecto, por ahora no debe realizar ningún cambio en este archivo pero puede revisarlo para entender mejor algunos de los siguientes pasos, el key `use_uuid` no se utiliza en los micro servicios.

### Paso #2

Publique los archivos de configuración general del paquete

```
php artisan vendor:publish --tag=laravel-microservices-config
```

Se publicará el siguiente archivo en su proyecto:

`config/background.php`
Este archivo contiene la configuración para resolver las solicitudes en segundo plano, dentro de él encontrará inicialmente el key `events` que se encarga de asociar un evento (un evento es el nombre asignado a una solicitud en segundo plano) con un el nombre de la clase que se ejecutará al recibir dicho evento. En la sección de comandos para micro servicios se explica como se crean los eventos y las clases asociadas.

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

### Paso #4

Agregue a la lista de aliases del archivo `config/app.php` la siguiente configuración para la clase de administración de Rabbit Mq

```
'Amqp' => Bschmitt\Amqp\Facades\Amqp::class
```

### Paso #5

Agregue la configuración de acceso al api gateway, esta configuración se agrega en el archivo `config/services.php`. En su archivo `.env` debe definir las variables de entorno `API_GATEWAY_PUBLIC_URL, API_GATEWAY_BASE_URI` y `API_GATEWAY_ACCESS_TOKEN`

```
'api_gateway' => [
    'public_url' => env('API_GATEWAY_PUBLIC_URL'),
    'base_uri' => env('API_GATEWAY_BASE_URI'),
    'access_token' => env('API_GATEWAY_ACCESS_TOKEN'),
]
```

`API_GATEWAY_PUBLIC_URL:` Esta es la url pública del api gateway, se utiliza para agregarla en la paginación de los modelos.</br>
`API_GATEWAY_BASE_URI:` Esta es la url que se utiliza para que los micro servicios creados realicen solicitudes a api gateway.</br>
`API_GATEWAY_ACCESS_TOKEN:` Este es el token de acceso al api gateway desde el micro servicio, este token se obtiene en el api gateway cuando se registra el micro servicio en la base de datos.</br>

### Paso #6

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

### Paso #7

Agregue el midleware `\Jose1805\LaravelMicroservices\Http\Middleware\Service\AuthenticateAccessMiddleware::class` en la variable `$middleware` del archivo `app\Http\Kernel.php` para validar que todas las peticiones contengan autenticación desde el api_gateway. Si solo desea validar algunas peticiones agregue el middleware `auth_api_gateway` a las rutas que desea que tengan validación.

### Paso #8

Edite su modelo `User` con el siguiente código, si no utiliza uuids elimine del siguiente código la línea que contiene `use HasUuids;`

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

Este comando crea un nuevo token de acceso al servicio y lo registra automáticamente en su archivo `.env`, debe utilizar un token creado con este comando para configurar un servicio el el api gateway

### Tareas en segundo plano

```
php artisan lm:make-resolver NombreTarea event=event-name
```

Ejecute el siguiente comando para crear y configurar una tarea para resolver en segundo plano.
El comando creará un archivo `app/Background/NombreTarea.php`, dentro de la clase incluida en el archivo encontrará un método `handle`, que recibe el nombre del evento y los datos de la petición en segundo plano, realice las tareas requeridas y retorne una respuesta que será enviada al api gateway. El comando también configura el archivo `config/background.php` para que el evento recibido en `--event` se asocie a la clase `NombreTarea`.

### Creación de recursos del servicio

```
 php artisan lm:make-resource ResourceName route=/example/example
```

Este comando se utiliza para crear un recurso con modelo, requests, controlador y rutas. El argumento `route` no es obligatorio, si no lo envía el sistema asigna automáticamente la ruta de acuerdo al nombre del recurso, si el resultado no es el esperado puede cambiar esto directamente en su archivo de rutas.
