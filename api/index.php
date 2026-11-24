<?php
// api/index.php - Punto de entrada (Front Controller) de la API MVC

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/EnvLoader.php';
require_once __DIR__ . '/helpers/ResponseHelper.php';
require_once __DIR__ . '/routes/api.php';

// Cargar variables de entorno desde la raíz del proyecto
EnvLoader::cargar(dirname(__DIR__) . '/.env');

try {
    despacharRutaApi();
} catch (PDOException $e) {
    // Error controlado de base de datos (sin exponer credenciales ni cadenas DSN)
    ResponseHelper::error(
        'Error al procesar la operación en la base de datos. Verifique la integridad de los datos enviados.',
        400
    );
} catch (Throwable $e) {
    $debug = EnvLoader::obtener('APP_DEBUG', 'false') === 'true';
    $detalle = $debug ? ResponseHelper::normalizarUtf8($e->getMessage()) : null;
    ResponseHelper::error('Error interno en el servidor de la API.', 500, $detalle);
}
