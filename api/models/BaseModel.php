<?php
// api/models/BaseModel.php

require_once __DIR__ . '/../config/Conexion.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getConexion();
    }

    protected function codificarEntrada(?string $valor): ?string
    {
        return ResponseHelper::aSqlServer($valor);
    }

    protected function limpiarSalida(array $fila): array
    {
        return ResponseHelper::normalizarUtf8($fila);
    }

    protected function limpiarListaSalida(array $filas): array
    {
        return ResponseHelper::normalizarUtf8($filas);
    }
}
