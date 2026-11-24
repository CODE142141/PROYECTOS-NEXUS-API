<?php
// api/models/DetallePedidoModel.php

require_once __DIR__ . '/BaseModel.php';

class DetallePedidoModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT d.id_detalle, d.id_pedido, d.id_producto,
                       pr.codigo_sku, pr.nombre AS producto_nombre,
                       d.cantidad, d.precio_unitario, d.importe_subtotal
                FROM Detalles_Pedido d
                INNER JOIN Productos pr ON d.id_producto = pr.id_producto
                ORDER BY d.id_detalle ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT d.id_detalle, d.id_pedido, d.id_producto,
                       pr.codigo_sku, pr.nombre AS producto_nombre,
                       d.cantidad, d.precio_unitario, d.importe_subtotal
                FROM Detalles_Pedido d
                INNER JOIN Productos pr ON d.id_producto = pr.id_producto
                WHERE d.id_detalle = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->limpiarSalida($fila) : null;
    }

    public function existePedido(int $idPedido): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Pedidos WHERE id_pedido = :id");
        $stmt->execute([':id' => $idPedido]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function obtenerProducto(int $idProducto): ?array
    {
        $stmt = $this->db->prepare("SELECT id_producto, precio_unitario, stock, CAST(activo AS INT) AS activo FROM Productos WHERE id_producto = :id");
        $stmt->execute([':id' => $idProducto]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    private function recalcularTotalesPedido(int $idPedido): void
    {
        $sqlSum = "SELECT COALESCE(SUM(importe_subtotal), 0) AS subtotal
                   FROM Detalles_Pedido
                   WHERE id_pedido = :id_pedido";
        $stmtSum = $this->db->prepare($sqlSum);
        $stmtSum->execute([':id_pedido' => $idPedido]);
        $subtotal = round((float) $stmtSum->fetch()['subtotal'], 2);
        $igv      = round($subtotal * 0.18, 2);
        $total    = round($subtotal + $igv, 2);

        $sqlUpd = "UPDATE Pedidos
                   SET subtotal = :subtotal, igv = :igv, total = :total
                   WHERE id_pedido = :id_pedido";
        $stmtUpd = $this->db->prepare($sqlUpd);
        $stmtUpd->execute([
            ':subtotal'  => $subtotal,
            ':igv'       => $igv,
            ':total'     => $total,
            ':id_pedido' => $idPedido,
        ]);
    }

    public function crear(array $datos): array
    {
        $idPedido   = (int) $datos['id_pedido'];
        $idProducto = (int) $datos['id_producto'];
        $cantidad   = (int) $datos['cantidad'];

        $producto = $this->obtenerProducto($idProducto);
        $precioUnitario = isset($datos['precio_unitario'])
            ? (float) $datos['precio_unitario']
            : (float) $producto['precio_unitario'];

        $sql = "INSERT INTO Detalles_Pedido (id_pedido, id_producto, cantidad, precio_unitario)
                OUTPUT INSERTED.id_detalle
                VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_pedido'       => $idPedido,
            ':id_producto'     => $idProducto,
            ':cantidad'        => $cantidad,
            ':precio_unitario' => $precioUnitario,
        ]);

        $nuevoId = (int) $stmt->fetch()['id_detalle'];
        $this->recalcularTotalesPedido($idPedido);
        return $this->obtenerPorId($nuevoId);
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return null;
        }

        $idPedidoAnterior = (int) $actual['id_pedido'];
        $idPedidoNuevo    = isset($datos['id_pedido']) ? (int) $datos['id_pedido'] : $idPedidoAnterior;
        $idProducto       = isset($datos['id_producto']) ? (int) $datos['id_producto'] : (int) $actual['id_producto'];
        $cantidad         = isset($datos['cantidad']) ? (int) $datos['cantidad'] : (int) $actual['cantidad'];
        $precioUnitario   = isset($datos['precio_unitario']) ? (float) $datos['precio_unitario'] : (float) $actual['precio_unitario'];

        $sql = "UPDATE Detalles_Pedido
                SET id_pedido = :id_pedido,
                    id_producto = :id_producto,
                    cantidad = :cantidad,
                    precio_unitario = :precio_unitario
                WHERE id_detalle = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_pedido'       => $idPedidoNuevo,
            ':id_producto'     => $idProducto,
            ':cantidad'        => $cantidad,
            ':precio_unitario' => $precioUnitario,
            ':id'              => $id,
        ]);

        $this->recalcularTotalesPedido($idPedidoAnterior);
        if ($idPedidoNuevo !== $idPedidoAnterior) {
            $this->recalcularTotalesPedido($idPedidoNuevo);
        }

        return $this->obtenerPorId($id);
    }

    public function eliminar(int $id): bool
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return false;
        }

        $idPedido = (int) $actual['id_pedido'];
        $stmt = $this->db->prepare("DELETE FROM Detalles_Pedido WHERE id_detalle = :id");
        $stmt->execute([':id' => $id]);
        $eliminado = $stmt->rowCount() > 0;

        if ($eliminado) {
            $this->recalcularTotalesPedido($idPedido);
        }

        return $eliminado;
    }
}
