<?php
// api/models/ProductoModel.php

require_once __DIR__ . '/BaseModel.php';

class ProductoModel extends BaseModel
{
    public function listar(): array
    {
        $sql = "SELECT p.id_producto, p.id_categoria, c.nombre AS categoria,
                       p.codigo_sku, p.nombre, p.descripcion,
                       p.precio_unitario, p.stock, CAST(p.activo AS INT) AS activo
                FROM Productos p
                INNER JOIN Categorias c ON p.id_categoria = c.id_categoria
                ORDER BY p.id_producto ASC";
        $stmt = $this->db->query($sql);
        return $this->limpiarListaSalida($stmt->fetchAll());
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT p.id_producto, p.id_categoria, c.nombre AS categoria,
                       p.codigo_sku, p.nombre, p.descripcion,
                       p.precio_unitario, p.stock, CAST(p.activo AS INT) AS activo
                FROM Productos p
                INNER JOIN Categorias c ON p.id_categoria = c.id_categoria
                WHERE p.id_producto = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $this->limpiarSalida($fila) : null;
    }

    public function existeCategoria(int $idCategoria): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Categorias WHERE id_categoria = :id");
        $stmt->execute([':id' => $idCategoria]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function existeSku(string $sku, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM Productos WHERE codigo_sku = :sku";
        $params = [':sku' => $this->codificarEntrada($sku)];
        if ($excluirId !== null) {
            $sql .= " AND id_producto <> :id";
            $params[':id'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function crear(array $datos): array
    {
        $sql = "INSERT INTO Productos (id_categoria, codigo_sku, nombre, descripcion, precio_unitario, stock, activo)
                OUTPUT INSERTED.id_producto
                VALUES (:id_categoria, :sku, :nombre, :descripcion, :precio, :stock, :activo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_categoria' => (int) $datos['id_categoria'],
            ':sku'          => $this->codificarEntrada(strtoupper(trim($datos['codigo_sku']))),
            ':nombre'       => $this->codificarEntrada(trim($datos['nombre'])),
            ':descripcion'  => isset($datos['descripcion']) ? $this->codificarEntrada($datos['descripcion']) : null,
            ':precio'       => (float) $datos['precio_unitario'],
            ':stock'        => isset($datos['stock']) ? (int) $datos['stock'] : 0,
            ':activo'       => isset($datos['activo']) ? (int) $datos['activo'] : 1,
        ]);
        $nuevoId = (int) $stmt->fetch()['id_producto'];
        return $this->obtenerPorId($nuevoId);
    }

    public function actualizar(int $id, array $datos): ?array
    {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return null;
        }

        $idCategoria = isset($datos['id_categoria']) ? (int) $datos['id_categoria'] : (int) $actual['id_categoria'];
        $sku         = isset($datos['codigo_sku']) ? strtoupper(trim($datos['codigo_sku'])) : $actual['codigo_sku'];
        $nombre      = isset($datos['nombre']) ? trim($datos['nombre']) : $actual['nombre'];
        $descripcion = array_key_exists('descripcion', $datos) ? $datos['descripcion'] : $actual['descripcion'];
        $precio      = isset($datos['precio_unitario']) ? (float) $datos['precio_unitario'] : (float) $actual['precio_unitario'];
        $stock       = isset($datos['stock']) ? (int) $datos['stock'] : (int) $actual['stock'];
        $activo      = isset($datos['activo']) ? (int) $datos['activo'] : (int) $actual['activo'];

        $sql = "UPDATE Productos
                SET id_categoria = :id_categoria,
                    codigo_sku = :sku,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio_unitario = :precio,
                    stock = :stock,
                    activo = :activo
                WHERE id_producto = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_categoria' => $idCategoria,
            ':sku'          => $this->codificarEntrada($sku),
            ':nombre'       => $this->codificarEntrada($nombre),
            ':descripcion'  => $descripcion !== null ? $this->codificarEntrada($descripcion) : null,
            ':precio'       => $precio,
            ':stock'        => $stock,
            ':activo'       => $activo,
            ':id'           => $id,
        ]);

        return $this->obtenerPorId($id);
    }

    public function tieneDetallesAsociados(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM Detalles_Pedido WHERE id_producto = :id");
        $stmt->execute([':id' => $id]);
        return ((int) $stmt->fetch()['total']) > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Productos WHERE id_producto = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
