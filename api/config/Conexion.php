<?php
// api/config/Conexion.php

require_once __DIR__ . '/EnvLoader.php';

class Conexion
{
    private static ?PDO $instancia = null;

    public static function getConexion(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        $driverPrincipal = EnvLoader::obtener('DB_DRIVER', 'ODBC Driver 17 for SQL Server');
        $driverRespaldo  = EnvLoader::obtener('DB_FALLBACK_DRIVER', 'SQL Server');
        $host            = EnvLoader::obtener('DB_HOST', 'localhost');
        $puerto          = EnvLoader::obtener('DB_PORT', '1433');
        $baseDatos       = EnvLoader::obtener('DB_NAME', 'NEXUS_ADMIN_DB');
        $usuario         = EnvLoader::obtener('DB_USER', 'sa');
        $password        = EnvLoader::obtener('DB_PASS', '');

        $servidor = ($puerto !== '' && !str_contains($host, ',') && !str_contains($host, '\\'))
            ? "{$host},{$puerto}"
            : $host;

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $dsn1 = "odbc:Driver={{$driverPrincipal}};Server={$servidor};Database={$baseDatos};Uid={$usuario};Pwd={$password};TrustServerCertificate=yes;";
            self::$instancia = new PDO($dsn1, null, null, $opciones);
            return self::$instancia;
        } catch (PDOException $e1) {
            try {
                $dsn2 = "odbc:Driver={{$driverRespaldo}};Server={$host};Database={$baseDatos};Uid={$usuario};Pwd={$password};";
                self::$instancia = new PDO($dsn2, null, null, $opciones);
                return self::$instancia;
            } catch (PDOException $e2) {
                throw new RuntimeException('No se pudo establecer conexión con el servidor de base de datos SQL Server. Verifique el servicio y las variables de entorno (.env).');
            }
        }
    }
}