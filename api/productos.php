<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

require_once 'conexion.php';

try {
    // 1. Consulta a las tablas de NEXUS_ADMIN_DB
    $stmt = $pdo->prepare("
        SELECT 
            p.id_producto, 
            p.codigo_sku, 
            p.nombre, 
            p.precio_unitario, 
            p.stock, 
            c.nombre AS categoria
        FROM Productos p
        INNER JOIN Categorias c ON p.id_categoria = c.id_categoria
        WHERE p.activo = 1
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    array_walk_recursive($productos, function (&$valor) {
        if (is_string($valor)) {
            $valor = mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
        }
    });

    echo json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al consultar productos: " . mb_convert_encoding($e->getMessage(), 'UTF-8', 'Windows-1252')
    ], JSON_UNESCAPED_UNICODE);
}