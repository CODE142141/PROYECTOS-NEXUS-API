<?php

$servidor = "localhost"; 
$baseDatos = "NEXUS_ADMIN_DB";
$usuario = "sa";
$password = "SqlAdmin2026*"; 

try {
    $dsn = "odbc:Driver={SQL Server};Server=$servidor;Database=$baseDatos;Uid=$usuario;Pwd=$password;";
    
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $mensaje = mb_convert_encoding($e->getMessage(), 'UTF-8', 'ISO-8859-1');
    echo json_encode(["error" => "Error de conexión: " . $mensaje]);
    exit;
}