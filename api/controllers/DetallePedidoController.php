<?php
// api/controllers/DetallePedidoController.php

require_once __DIR__ . '/../models/DetallePedidoModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class DetallePedidoController
{
    private DetallePedidoModel $modelo;

    public function __construct()
    {
        $this->modelo = new DetallePedidoModel();
    }

    public function listar(): void
    {
        $detalles = $this->modelo->listar();
        ResponseHelper::exito('Listado de detalles de pedido obtenido correctamente.', $detalles, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_detalle');
        $detalle = $this->modelo->obtenerPorId($id);

        if (!$detalle) {
            ResponseHelper::error("No se encontró el detalle de pedido con ID {$id}.", 404);
        }

        ResponseHelper::exito('Detalle de pedido encontrado.', $detalle, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        $obligatorios = ['id_pedido', 'id_producto', 'cantidad'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo])) {
                ResponseHelper::error("El campo '{$campo}' es obligatorio.", 400);
            }
        }

        $idPedido   = ResponseHelper::validarId($datos['id_pedido'], 'id_pedido');
        $idProducto = ResponseHelper::validarId($datos['id_producto'], 'id_producto');
        $cantidad   = ResponseHelper::validarId($datos['cantidad'], 'cantidad');

        if (!$this->modelo->existePedido($idPedido)) {
            ResponseHelper::error("El pedido especificado (id_pedido: {$idPedido}) no existe.", 400);
        }

        $producto = $this->modelo->obtenerProducto($idProducto);
        if (!$producto) {
            ResponseHelper::error("El producto especificado (id_producto: {$idProducto}) no existe.", 400);
        }

        if (isset($datos['precio_unitario']) && (!is_numeric($datos['precio_unitario']) || (float) $datos['precio_unitario'] <= 0)) {
            ResponseHelper::error('El campo "precio_unitario" debe ser un número mayor a 0.', 400);
        }

        $datos['id_pedido']   = $idPedido;
        $datos['id_producto'] = $idProducto;
        $datos['cantidad']    = $cantidad;

        $creado = $this->modelo->crear($datos);
        ResponseHelper::exito('Detalle de pedido agregado correctamente.', $creado, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_detalle');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el detalle de pedido con ID {$id}.", 404);
        }

        if (isset($datos['id_pedido'])) {
            $idPedido = ResponseHelper::validarId($datos['id_pedido'], 'id_pedido');
            if (!$this->modelo->existePedido($idPedido)) {
                ResponseHelper::error("El pedido especificado (id_pedido: {$idPedido}) no existe.", 400);
            }
            $datos['id_pedido'] = $idPedido;
        }

        if (isset($datos['id_producto'])) {
            $idProducto = ResponseHelper::validarId($datos['id_producto'], 'id_producto');
            if (!$this->modelo->obtenerProducto($idProducto)) {
                ResponseHelper::error("El producto especificado (id_producto: {$idProducto}) no existe.", 400);
            }
            $datos['id_producto'] = $idProducto;
        }

        if (isset($datos['cantidad'])) {
            $datos['cantidad'] = ResponseHelper::validarId($datos['cantidad'], 'cantidad');
        }

        if (isset($datos['precio_unitario']) && (!is_numeric($datos['precio_unitario']) || (float) $datos['precio_unitario'] <= 0)) {
            ResponseHelper::error('El campo "precio_unitario" debe ser mayor a 0.', 400);
        }

        $actualizado = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Detalle de pedido actualizado correctamente.', $actualizado, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_detalle');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el detalle de pedido con ID {$id}.", 404);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Detalle de pedido con ID {$id} eliminado correctamente.", null, 200);
    }
}
