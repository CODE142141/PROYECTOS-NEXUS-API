<?php
// api/models/UsuarioModel.php

require_once __DIR__ . '/BaseModel.php';

class UsuarioModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT u.id_usuario, u.id_rol, r.nombre AS rol_nombre, u.nombre, u.email,
                       u.telefono, CAST(u.estado AS INT) AS estado, u.fecha_registro
                FROM Usuarios u
                INNER JOIN Roles r ON u.id_rol = r.id_rol
                ORDER BY u.id_usuario ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT u.id_usuario, u.id_rol, r.nombre AS rol_nombre, u.nombre, u.email,
                       u.telefono, CAST(u.estado AS INT) AS estado, u.fecha_registro
                FROM Usuarios u
                INNER JOIN Roles r ON u.id_rol = r.id_rol
                WHERE u.id_usuario = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->limpiarSalida($fila) : null;
    }

    public function existeRol(int $idRol): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Roles WHERE id_rol = :id_rol");
        $stmt->execute([':id_rol' => $idRol]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function existeEmail(string $email, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM Usuarios WHERE email = :email";
        $params = [':email' => $this->codificarEntrada($email)];
        if ($excluirId !== null) {
            $sql .= " AND id_usuario <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function crear(array $datos): array
    {
        $claveHash = password_hash((string) $datos['clave'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO Usuarios (id_rol, nombre, email, clave_hash, telefono, estado)
                OUTPUT INSERTED.id_usuario
                VALUES (:id_rol, :nombre, :email, :clave_hash, :telefono, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_rol'     => (int) $datos['id_rol'],
            ':nombre'     => $this->codificarEntrada($datos['nombre']),
            ':email'      => $this->codificarEntrada($datos['email']),
            ':clave_hash' => $claveHash,
            ':telefono'   => isset($datos['telefono']) ? $this->codificarEntrada($datos['telefono']) : null,
            ':estado'     => isset($datos['estado']) ? (int) $datos['estado'] : 1,
        ]);
        $nuevoId = (int) $stmt->fetch()['id_usuario'];
        return $this->obtenerPorId($nuevoId);
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return null;
        }

        $idRol    = isset($datos['id_rol']) ? (int) $datos['id_rol'] : (int) $actual['id_rol'];
        $nombre   = $datos['nombre'] ?? $actual['nombre'];
        $email    = $datos['email'] ?? $actual['email'];
        $telefono = array_key_exists('telefono', $datos) ? $datos['telefono'] : $actual['telefono'];
        $estado   = isset($datos['estado']) ? (int) $datos['estado'] : (int) $actual['estado'];

        if (!empty($datos['clave'])) {
            $claveHash = password_hash((string) $datos['clave'], PASSWORD_BCRYPT);
            $sql = "UPDATE Usuarios
                    SET id_rol = :id_rol, nombre = :nombre, email = :email,
                        clave_hash = :clave_hash, telefono = :telefono, estado = :estado
                    WHERE id_usuario = :id";
            $params = [
                ':id_rol'     => $idRol,
                ':nombre'     => $this->codificarEntrada($nombre),
                ':email'      => $this->codificarEntrada($email),
                ':clave_hash' => $claveHash,
                ':telefono'   => $telefono !== null ? $this->codificarEntrada($telefono) : null,
                ':estado'     => $estado,
                ':id'         => $id,
            ];
        } else {
            $sql = "UPDATE Usuarios
                    SET id_rol = :id_rol, nombre = :nombre, email = :email,
                        telefono = :telefono, estado = :estado
                    WHERE id_usuario = :id";
            $params = [
                ':id_rol'   => $idRol,
                ':nombre'   => $this->codificarEntrada($nombre),
                ':email'    => $this->codificarEntrada($email),
                ':telefono' => $telefono !== null ? $this->codificarEntrada($telefono) : null,
                ':estado'   => $estado,
                ':id'       => $id,
            ];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->obtenerPorId($id);
    }

    public function tienePedidosAsociados(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Pedidos WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
