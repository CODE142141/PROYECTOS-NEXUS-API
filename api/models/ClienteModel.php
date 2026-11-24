<?php
// api/models/ClienteModel.php

require_once __DIR__ . '/BaseModel.php';

class ClienteModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT id_cliente, tipo_documento, numero_documento, nombres_razon_social,
                       correo, telefono, direccion, fecha_creacion
                FROM Clientes
                ORDER BY id_cliente ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT id_cliente, tipo_documento, numero_documento, nombres_razon_social,
                       correo, telefono, direccion, fecha_creacion
                FROM Clientes
                WHERE id_cliente = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->limpiarSalida($fila) : null;
    }

    public function existeDocumento(string $numeroDocumento, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM Clientes WHERE numero_documento = :num";
        $params = [':num' => $this->codificarEntrada($numeroDocumento)];
        if ($excluirId !== null) {
            $sql .= " AND id_cliente <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function crear(array $datos): array
    {
        $sql = "INSERT INTO Clientes (tipo_documento, numero_documento, nombres_razon_social, correo, telefono, direccion)
                OUTPUT INSERTED.id_cliente
                VALUES (:tipo_doc, :num_doc, :nombres, :correo, :telefono, :direccion)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tipo_doc'  => strtoupper(trim($datos['tipo_documento'] ?? 'DNI')),
            ':num_doc'   => $this->codificarEntrada(trim($datos['numero_documento'])),
            ':nombres'   => $this->codificarEntrada(trim($datos['nombres_razon_social'])),
            ':correo'    => $this->codificarEntrada(trim($datos['correo'])),
            ':telefono'  => isset($datos['telefono']) ? $this->codificarEntrada(trim($datos['telefono'])) : null,
            ':direccion' => isset($datos['direccion']) ? $this->codificarEntrada(trim($datos['direccion'])) : null,
        ]);
        $nuevoId = (int) $stmt->fetch()['id_cliente'];
        return $this->obtenerPorId($nuevoId);
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return null;
        }

        $tipoDoc   = isset($datos['tipo_documento']) ? strtoupper(trim($datos['tipo_documento'])) : $actual['tipo_documento'];
        $numDoc    = isset($datos['numero_documento']) ? trim($datos['numero_documento']) : $actual['numero_documento'];
        $nombres   = isset($datos['nombres_razon_social']) ? trim($datos['nombres_razon_social']) : $actual['nombres_razon_social'];
        $correo    = isset($datos['correo']) ? trim($datos['correo']) : $actual['correo'];
        $telefono  = array_key_exists('telefono', $datos) ? $datos['telefono'] : $actual['telefono'];
        $direccion = array_key_exists('direccion', $datos) ? $datos['direccion'] : $actual['direccion'];

        $sql = "UPDATE Clientes
                SET tipo_documento = :tipo_doc,
                    numero_documento = :num_doc,
                    nombres_razon_social = :nombres,
                    correo = :correo,
                    telefono = :telefono,
                    direccion = :direccion
                WHERE id_cliente = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':tipo_doc'  => $tipoDoc,
            ':num_doc'   => $this->codificarEntrada($numDoc),
            ':nombres'   => $this->codificarEntrada($nombres),
            ':correo'    => $this->codificarEntrada($correo),
            ':telefono'  => $telefono !== null ? $this->codificarEntrada($telefono) : null,
            ':direccion' => $direccion !== null ? $this->codificarEntrada($direccion) : null,
            ':id'        => $id,
        ]);

        return $this->obtenerPorId($id);
    }

    public function tienePedidosAsociados(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Pedidos WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
