<?php
// api/models/PedidoModel.php

require_once __DIR__ . '/BaseModel.php';

class PedidoModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT p.id_pedido, p.id_cliente, c.nombres_razon_social AS cliente_nombre,
                       p.id_usuario, u.nombre AS usuario_nombre,
                       p.fecha_pedido, p.estado, p.subtotal, p.igv, p.total
                FROM Pedidos p
                INNER JOIN Clientes c ON p.id_cliente = c.id_cliente
                INNER JOIN Usuarios u ON p.id_usuario = u.id_usuario
                ORDER BY p.id_pedido ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT p.id_pedido, p.id_cliente, c.nombres_razon_social AS cliente_nombre,
                       p.id_usuario, u.nombre AS usuario_nombre,
                       p.fecha_pedido, p.estado, p.subtotal, p.igv, p.total
                FROM Pedidos p
                INNER JOIN Clientes c ON p.id_cliente = c.id_cliente
                INNER JOIN Usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.id_pedido = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            return null;
        }

        $pedido = $this->limpiarSalida($pedido);

        // Incluir las líneas de detalle asociadas al pedido
        $sqlDet = "SELECT d.id_detalle, d.id_producto, pr.codigo_sku, pr.nombre AS producto_nombre,
                          d.cantidad, d.precio_unitario, d.importe_subtotal
                   FROM Detalles_Pedido d
                   INNER JOIN Productos pr ON d.id_producto = pr.id_producto
                   WHERE d.id_pedido = :id
                   ORDER BY d.id_detalle ASC";
        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute([':id' => $id]);
        $pedido['detalles'] = $this->limpiarListaSalida($stmtDet->fetchAll());

        return $pedido;
    }

    public function existeCliente(int $idCliente): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $idCliente]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function existeUsuario(int $idUsuario): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $idUsuario]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function crear(array $datos): array
    {
        // Si se envía id_producto y cantidad, usar el procedimiento almacenado transaccional
        if (isset($datos['id_producto']) && isset($datos['cantidad'])) {
            $sqlSp = "DECLARE @nuevo_id INT;
                      EXEC sp_RegistrarPedido
                          @id_cliente = :id_cliente,
                          @id_usuario = :id_usuario,
                          @id_producto = :id_producto,
                          @cantidad = :cantidad,
                          @nuevo_id_pedido = @nuevo_id OUTPUT;
                      SELECT @nuevo_id AS id_pedido;";
            $stmtSp = $this->db->prepare($sqlSp);
            $stmtSp->execute([
                ':id_cliente'  => (int) $datos['id_cliente'],
                ':id_usuario'  => (int) $datos['id_usuario'],
                ':id_producto' => (int) $datos['id_producto'],
                ':cantidad'    => (int) $datos['cantidad'],
            ]);
            $res = $stmtSp->fetch();
            $nuevoId = (int) $res['id_pedido'];
            return $this->obtenerPorId($nuevoId);
        }

        // Creación estándar de cabecera de pedido
        $estado   = strtoupper(trim($datos['estado'] ?? 'PENDIENTE'));
        $subtotal = isset($datos['subtotal']) ? round((float) $datos['subtotal'], 2) : 0.00;
        $igv      = isset($datos['igv']) ? round((float) $datos['igv'], 2) : round($subtotal * 0.18, 2);
        $total    = isset($datos['total']) ? round((float) $datos['total'], 2) : round($subtotal + $igv, 2);

        $sql = "INSERT INTO Pedidos (id_cliente, id_usuario, estado, subtotal, igv, total)
                OUTPUT INSERTED.id_pedido
                VALUES (:id_cliente, :id_usuario, :estado, :subtotal, :igv, :total)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_cliente' => (int) $datos['id_cliente'],
            ':id_usuario' => (int) $datos['id_usuario'],
            ':estado'     => $estado,
            ':subtotal'   => $subtotal,
            ':igv'        => $igv,
            ':total'      => $total,
        ]);
        $nuevoId = (int) $stmt->fetch()['id_pedido'];
        return $this->obtenerPorId($nuevoId);
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return null;
        }

        $idCliente = isset($datos['id_cliente']) ? (int) $datos['id_cliente'] : (int) $actual['id_cliente'];
        $idUsuario = isset($datos['id_usuario']) ? (int) $datos['id_usuario'] : (int) $actual['id_usuario'];
        $estado    = isset($datos['estado']) ? strtoupper(trim($datos['estado'])) : $actual['estado'];
        $subtotal  = isset($datos['subtotal']) ? round((float) $datos['subtotal'], 2) : (float) $actual['subtotal'];
        $igv       = isset($datos['igv']) ? round((float) $datos['igv'], 2) : (float) $actual['igv'];
        $total     = isset($datos['total']) ? round((float) $datos['total'], 2) : (float) $actual['total'];

        $sql = "UPDATE Pedidos
                SET id_cliente = :id_cliente,
                    id_usuario = :id_usuario,
                    estado = :estado,
                    subtotal = :subtotal,
                    igv = :igv,
                    total = :total
                WHERE id_pedido = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_cliente' => $idCliente,
            ':id_usuario' => $idUsuario,
            ':estado'     => $estado,
            ':subtotal'   => $subtotal,
            ':igv'        => $igv,
            ':total'      => $total,
            ':id'         => $id,
        ]);

        return $this->obtenerPorId($id);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Pedidos WHERE id_pedido = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
