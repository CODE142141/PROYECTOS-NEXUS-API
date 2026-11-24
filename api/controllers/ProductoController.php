<?php
// api/controllers/ProductoController.php

require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class ProductoController
{
    private ProductoModel $modelo;

    public function __construct()
    {
        $this->modelo = new ProductoModel();
    }

    public function listar(): void
    {
        $productos = $this->modelo->listar();
        ResponseHelper::exito('Listado de productos obtenido correctamente.', $productos, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_producto');
        $producto = $this->modelo->obtenerPorId($id);

        if (!$producto) {
            ResponseHelper::error("No se encontró el producto con ID {$id}.", 404);
        }

        ResponseHelper::exito('Producto encontrado.', $producto, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        $obligatorios = ['id_categoria', 'codigo_sku', 'nombre', 'precio_unitario'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                ResponseHelper::error("El campo '{$campo}' es obligatorio.", 400);
            }
        }

        $idCategoria = ResponseHelper::validarId($datos['id_categoria'], 'id_categoria');
        if (!$this->modelo->existeCategoria($idCategoria)) {
            ResponseHelper::error("La categoría especificada (id_categoria: {$idCategoria}) no existe.", 400);
        }

        $sku = strtoupper(trim((string) $datos['codigo_sku']));
        if ($this->modelo->existeSku($sku)) {
            ResponseHelper::error("Ya existe un producto con el SKU '{$sku}'.", 400);
        }

        if (!is_numeric($datos['precio_unitario']) || (float) $datos['precio_unitario'] <= 0) {
            ResponseHelper::error('El campo "precio_unitario" debe ser un número mayor a 0.', 400);
        }

        if (isset($datos['stock']) && (!is_numeric($datos['stock']) || (int) $datos['stock'] < 0)) {
            ResponseHelper::error('El campo "stock" debe ser un entero mayor o igual a 0.', 400);
        }

        $datos['id_categoria'] = $idCategoria;
        $datos['codigo_sku']   = $sku;

        $creado = $this->modelo->crear($datos);
        ResponseHelper::exito('Producto creado correctamente.', $creado, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_producto');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el producto con ID {$id}.", 404);
        }

        if (isset($datos['id_categoria'])) {
            $idCategoria = ResponseHelper::validarId($datos['id_categoria'], 'id_categoria');
            if (!$this->modelo->existeCategoria($idCategoria)) {
                ResponseHelper::error("La categoría especificada (id_categoria: {$idCategoria}) no existe.", 400);
            }
            $datos['id_categoria'] = $idCategoria;
        }

        if (isset($datos['codigo_sku'])) {
            $sku = strtoupper(trim((string) $datos['codigo_sku']));
            if ($sku === '' || $this->modelo->existeSku($sku, $id)) {
                ResponseHelper::error("El código SKU '{$sku}' es inválido o ya pertenece a otro producto.", 400);
            }
            $datos['codigo_sku'] = $sku;
        }

        if (isset($datos['precio_unitario']) && (!is_numeric($datos['precio_unitario']) || (float) $datos['precio_unitario'] <= 0)) {
            ResponseHelper::error('El campo "precio_unitario" debe ser mayor a 0.', 400);
        }

        if (isset($datos['stock']) && (!is_numeric($datos['stock']) || (int) $datos['stock'] < 0)) {
            ResponseHelper::error('El campo "stock" debe ser mayor o igual a 0.', 400);
        }

        $actualizado = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Producto actualizado correctamente.', $actualizado, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_producto');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el producto con ID {$id}.", 404);
        }

        if ($this->modelo->tieneDetallesAsociados($id)) {
            ResponseHelper::error('No se puede eliminar el producto porque figura en detalles de pedidos existentes.', 400);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Producto con ID {$id} eliminado correctamente.", null, 200);
    }
}
