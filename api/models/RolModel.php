<?php
// api/models/RolModel.php

require_once __DIR__ . '/BaseModel.php';

class RolModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT id_rol, nombre, descripcion, CAST(estado AS INT) AS estado, fecha_creacion
                FROM Roles
                ORDER BY id_rol ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT id_rol, nombre, descripcion, CAST(estado AS INT) AS estado, fecha_creacion
                FROM Roles
                WHERE id_rol = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->limpiarSalida($fila) : null;
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM Roles WHERE nombre = :nombre";
        $params = [':nombre' => $this->codificarEntrada($nombre)];
        if ($excluirId !== null) {
            $sql .= " AND id_rol <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function crear(array $datos): array
    {
        $sql = "INSERT INTO Roles (nombre, descripcion, estado)
                OUTPUT INSERTED.id_rol
                VALUES (:nombre, :descripcion, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $this->codificarEntrada($datos['nombre']),
            ':descripcion' => isset($datos['descripcion']) ? $this->codificarEntrada($datos['descripcion']) : null,
            ':estado'      => isset($datos['estado']) ? (int) $datos['estado'] : 1,
        ]);
        $nuevoId = (int) $stmt->fetch()['id_rol'];
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
        $estado      = isset($datos['estado']) ? (int) $datos['estado'] : (int) $actual['estado'];

        $sql = "UPDATE Roles
                SET nombre = :nombre, descripcion = :descripcion, estado = :estado
                WHERE id_rol = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'      => $this->codificarEntrada($nombre),
            ':descripcion' => $descripcion !== null ? $this->codificarEntrada($descripcion) : null,
            ':estado'      => $estado,
            ':id'          => $id,
        ]);

        return $this->obtenerPorId($id);
    }

    public function tieneUsuariosAsociados(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Usuarios WHERE id_rol = :id");
        $stmt->execute([':id' => $id]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Roles WHERE id_rol = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
