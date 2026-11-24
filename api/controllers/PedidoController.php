<?php
// api/controllers/PedidoController.php

require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class PedidoController
{
    private PedidoModel $modelo;
    private array $estadosPermitidos = ['PENDIENTE', 'PAGADO', 'ANULADO', 'ENVIADO'];

    public function __construct()
    {
        $this->modelo = new PedidoModel();
    }

    public function listar(): void
    {
        $pedidos = $this->modelo->listar();
        ResponseHelper::exito('Listado de pedidos obtenido correctamente.', $pedidos, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_pedido');
        $pedido = $this->modelo->obtenerPorId($id);

        if (!$pedido) {
            ResponseHelper::error("No se encontró el pedido con ID {$id}.", 404);
        }

        ResponseHelper::exito('Pedido encontrado.', $pedido, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (!isset($datos['id_cliente']) || !isset($datos['id_usuario'])) {
            ResponseHelper::error('Los campos "id_cliente" e "id_usuario" son obligatorios.', 400);
        }

        $idCliente = ResponseHelper::validarId($datos['id_cliente'], 'id_cliente');
        $idUsuario = ResponseHelper::validarId($datos['id_usuario'], 'id_usuario');

        if (!$this->modelo->existeCliente($idCliente)) {
            ResponseHelper::error("El cliente especificado (id_cliente: {$idCliente}) no existe.", 400);
        }

        if (!$this->modelo->existeUsuario($idUsuario)) {
            ResponseHelper::error("El usuario especificado (id_usuario: {$idUsuario}) no existe.", 400);
        }

        if (isset($datos['estado'])) {
            $estado = strtoupper(trim((string) $datos['estado']));
            if (!in_array($estado, $this->estadosPermitidos, true)) {
                ResponseHelper::error('El campo "estado" debe ser PENDIENTE, PAGADO, ANULADO o ENVIADO.', 400);
            }
            $datos['estado'] = $estado;
        }

        foreach (['subtotal', 'igv', 'total'] as $monto) {
            if (isset($datos[$monto]) && (!is_numeric($datos[$monto]) || (float) $datos[$monto] < 0)) {
                ResponseHelper::error("El campo '{$monto}' debe ser un número mayor o igual a 0.", 400);
            }
        }

        if (isset($datos['id_producto']) || isset($datos['cantidad'])) {
            ResponseHelper::validarId($datos['id_producto'] ?? null, 'id_producto');
            ResponseHelper::validarId($datos['cantidad'] ?? null, 'cantidad');
        }

        $datos['id_cliente'] = $idCliente;
        $datos['id_usuario'] = $idUsuario;

        $creado = $this->modelo->crear($datos);
        ResponseHelper::exito('Pedido registrado correctamente.', $creado, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_pedido');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el pedido con ID {$id}.", 404);
        }

        if (isset($datos['id_cliente'])) {
            $idCliente = ResponseHelper::validarId($datos['id_cliente'], 'id_cliente');
            if (!$this->modelo->existeCliente($idCliente)) {
                ResponseHelper::error("El cliente especificado (id_cliente: {$idCliente}) no existe.", 400);
            }
            $datos['id_cliente'] = $idCliente;
        }

        if (isset($datos['id_usuario'])) {
            $idUsuario = ResponseHelper::validarId($datos['id_usuario'], 'id_usuario');
            if (!$this->modelo->existeUsuario($idUsuario)) {
                ResponseHelper::error("El usuario especificado (id_usuario: {$idUsuario}) no existe.", 400);
            }
            $datos['id_usuario'] = $idUsuario;
        }

        if (isset($datos['estado'])) {
            $estado = strtoupper(trim((string) $datos['estado']));
            if (!in_array($estado, $this->estadosPermitidos, true)) {
                ResponseHelper::error('El campo "estado" debe ser PENDIENTE, PAGADO, ANULADO o ENVIADO.', 400);
            }
            $datos['estado'] = $estado;
        }

        foreach (['subtotal', 'igv', 'total'] as $monto) {
            if (isset($datos[$monto]) && (!is_numeric($datos[$monto]) || (float) $datos[$monto] < 0)) {
                ResponseHelper::error("El campo '{$monto}' debe ser un número mayor o igual a 0.", 400);
            }
        }

        $actualizado = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Pedido actualizado correctamente.', $actualizado, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_pedido');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el pedido con ID {$id}.", 404);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Pedido con ID {$id} y sus detalles fueron eliminados correctamente.", null, 200);
    }
}
