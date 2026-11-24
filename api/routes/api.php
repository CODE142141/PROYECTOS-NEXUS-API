<?php
// api/routes/api.php

require_once __DIR__ . '/../helpers/ResponseHelper.php';
require_once __DIR__ . '/../controllers/RolController.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/CategoriaController.php';
require_once __DIR__ . '/../controllers/ClienteController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/PedidoController.php';
require_once __DIR__ . '/../controllers/DetallePedidoController.php';

function despacharRutaApi(): void
{
    $metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uriPath    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

    // Remover el prefijo de carpeta (/PROYECTOS-NEXUS/api) y /index.php si está presente
    $rutaRelativa = $uriPath;
    if ($scriptDir !== '' && str_starts_with($rutaRelativa, $scriptDir)) {
        $rutaRelativa = substr($rutaRelativa, strlen($scriptDir));
    }
    if (str_starts_with($rutaRelativa, '/index.php')) {
        $rutaRelativa = substr($rutaRelativa, strlen('/index.php'));
    }

    $rutaRelativa = trim($rutaRelativa, '/');

    // Ruta raíz de la API: muestra información de los módulos disponibles
    if ($rutaRelativa === '') {
        ResponseHelper::exito('API REST MVC - Nexus Solutions operativa.', [
            'version' => '1.0.0',
            'modulos' => [
                'roles'           => '/api/roles',
                'usuarios'        => '/api/usuarios',
                'categorias'      => '/api/categorias',
                'clientes'        => '/api/clientes',
                'productos'       => '/api/productos',
                'pedidos'         => '/api/pedidos',
                'detalles_pedido' => '/api/detalles-pedido',
            ],
            'metodos_soportados' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        ], 200);
    }

    $segmentos = explode('/', $rutaRelativa);
    if (count($segmentos) > 2) {
        ResponseHelper::error('La ruta solicitada no es válida.', 404);
    }

    $recurso = strtolower($segmentos[0]);
    $id      = $segmentos[1] ?? null;

    $mapaControladores = [
        'roles'           => RolController::class,
        'usuarios'        => UsuarioController::class,
        'categorias'      => CategoriaController::class,
        'clientes'        => ClienteController::class,
        'productos'       => ProductoController::class,
        'pedidos'         => PedidoController::class,
        'detalles-pedido' => DetallePedidoController::class,
        'detalles_pedido' => DetallePedidoController::class,
    ];

    if (!isset($mapaControladores[$recurso])) {
        ResponseHelper::error("El recurso '{$recurso}' no existe en la API.", 404);
    }

    $claseControlador = $mapaControladores[$recurso];
    $controlador = new $claseControlador();

    switch ($metodoHttp) {
        case 'GET':
            if ($id === null) {
                $controlador->listar();
            } else {
                $controlador->obtenerPorId($id);
            }
            break;

        case 'POST':
            if ($id !== null) {
                ResponseHelper::error('No debe enviar un ID en la URL al crear un registro (POST).', 400);
            }
            $controlador->crear();
            break;

        case 'PUT':
        case 'PATCH':
            if ($id === null) {
                ResponseHelper::error('Debe indicar el ID del registro en la URL para actualizar (PUT/PATCH).', 400);
            }
            $controlador->actualizar($id);
            break;

        case 'DELETE':
            if ($id === null) {
                ResponseHelper::error('Debe indicar el ID del registro en la URL para eliminar (DELETE).', 400);
            }
            $controlador->eliminar($id);
            break;

        default:
            ResponseHelper::error("Método HTTP '{$metodoHttp}' no permitido.", 405);
            break;
    }
}
