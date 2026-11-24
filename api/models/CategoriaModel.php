<?php
// api/models/CategoriaModel.php

require_once __DIR__ . '/BaseModel.php';

class CategoriaModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT id_categoria, nombre, descripcion, CAST(activo AS INT) AS activo
                FROM Categorias
                ORDER BY id_categoria ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT id_categoria, nombre, descripcion, CAST(activo AS INT) AS activo
                FROM Categorias
                WHERE id_categoria = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->limpiarSalida($fila) : null;
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM Categorias WHERE nombre = :nombre";
        $params = [':nombre' => $this->codificarEntrada($nombre)];
        if ($excluirId !== null) {
            $sql .= " AND id_categoria <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function crear(array $datos): array
    {
        $sql = "INSERT INTO Categorias (nombre, descripcion, activo)
                OUTPUT INSERTED.id_categoria
                VALUES (:nombre, :descripcion, :activo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $this->codificarEntrada($datos['nombre']),
            ':descripcion' => isset($datos['descripcion']) ? $this->codificarEntrada($datos['descripcion']) : null,
            ':activo'      => isset($datos['activo']) ? (int) $datos['activo'] : 1,
        ]);
        $nuevoId = (int) $stmt->fetch()['id_categoria'];
        return $this->obtenerPorId($nuevoId);
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return null;
        }

        $nombre      = $datos['nombre'] ?? $actual['nombre'];
        $descripcion = array_key_exists('descripcion', $datos) ? $datos['descripcion'] : $actual['descripcion'];
        $activo      = isset($datos['activo']) ? (int) $datos['activo'] : (int) $actual['activo'];

        $sql = "UPDATE Categorias
                SET nombre = :nombre, descripcion = :descripcion, activo = :activo
                WHERE id_categoria = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $this->codificarEntrada($nombre),
            ':descripcion' => $descripcion !== null ? $this->codificarEntrada($descripcion) : null,
            ':activo'      => $activo,
            ':id'          => $id,
        ]);

        return $this->obtenerPorId($id);
    }

    public function tieneProductosAsociados(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Productos WHERE id_categoria = :id");
        $stmt->execute([':id' => $id]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Categorias WHERE id_categoria = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
