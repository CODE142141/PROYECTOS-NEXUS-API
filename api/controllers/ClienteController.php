<?php
// api/controllers/ClienteController.php

require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class ClienteController
{
    private ClienteModel $modelo;
    private array $tiposPermitidos = ['DNI', 'RUC', 'CE', 'PASAPORTE'];

    public function __construct()
    {
        $this->modelo = new ClienteModel();
    }

    public function listar(): void
    {
        $clientes = $this->modelo->listar();
        ResponseHelper::exito('Listado de clientes obtenido correctamente.', $clientes, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_cliente');
        $cliente = $this->modelo->obtenerPorId($id);

        if (!$cliente) {
            ResponseHelper::error("No se encontró el cliente con ID {$id}.", 404);
        }

        ResponseHelper::exito('Cliente encontrado.', $cliente, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        $obligatorios = ['numero_documento', 'nombres_razon_social', 'correo'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                ResponseHelper::error("El campo '{$campo}' es obligatorio.", 400);
            }
        }

        $tipoDoc = strtoupper(trim((string) ($datos['tipo_documento'] ?? 'DNI')));
        if (!in_array($tipoDoc, $this->tiposPermitidos, true)) {
            ResponseHelper::error('El campo "tipo_documento" debe ser DNI, RUC, CE o PASAPORTE.', 400);
        }

        $numDoc = trim((string) $datos['numero_documento']);
        if ($tipoDoc === 'DNI' && !preg_match('/^[0-9]{8}$/', $numDoc)) {
            ResponseHelper::error('El número de DNI debe contener exactamente 8 dígitos numéricos.', 400);
        }
        if ($tipoDoc === 'RUC' && !preg_match('/^[0-9]{11}$/', $numDoc)) {
            ResponseHelper::error('El número de RUC debe contener exactamente 11 dígitos numéricos.', 400);
        }

        if ($this->modelo->existeDocumento($numDoc)) {
            ResponseHelper::error("Ya existe un cliente con el documento '{$numDoc}'.", 400);
        }

        $correo = trim((string) $datos['correo']);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('El formato del campo "correo" no es válido.', 400);
        }

        $datos['tipo_documento'] = $tipoDoc;
        $creado = $this->modelo->crear($datos);
        ResponseHelper::exito('Cliente registrado correctamente.', $creado, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_cliente');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        $actual = $this->modelo->obtenerPorId($id);
        if (!$actual) {
            ResponseHelper::error("No se encontró el cliente con ID {$id}.", 404);
        }

        $tipoDoc = isset($datos['tipo_documento'])
            ? strtoupper(trim((string) $datos['tipo_documento']))
            : $actual['tipo_documento'];

        if (!in_array($tipoDoc, $this->tiposPermitidos, true)) {
            ResponseHelper::error('El campo "tipo_documento" debe ser DNI, RUC, CE o PASAPORTE.', 400);
        }

        if (isset($datos['numero_documento'])) {
            $numDoc = trim((string) $datos['numero_documento']);
            if ($tipoDoc === 'DNI' && !preg_match('/^[0-9]{8}$/', $numDoc)) {
                ResponseHelper::error('El número de DNI debe contener exactamente 8 dígitos numéricos.', 400);
            }
            if ($tipoDoc === 'RUC' && !preg_match('/^[0-9]{11}$/', $numDoc)) {
                ResponseHelper::error('El número de RUC debe contener exactamente 11 dígitos numéricos.', 400);
            }
            if ($this->modelo->existeDocumento($numDoc, $id)) {
                ResponseHelper::error("Ya existe otro cliente con el documento '{$numDoc}'.", 400);
            }
            $datos['numero_documento'] = $numDoc;
        }

        if (isset($datos['correo']) && !filter_var(trim((string) $datos['correo']), FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('El formato del campo "correo" no es válido.', 400);
        }

        $datos['tipo_documento'] = $tipoDoc;
        $actualizado = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Cliente actualizado correctamente.', $actualizado, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_cliente');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el cliente con ID {$id}.", 404);
        }

        if ($this->modelo->tienePedidosAsociados($id)) {
            ResponseHelper::error('No se puede eliminar el cliente porque tiene pedidos registrados.', 400);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Cliente con ID {$id} eliminado correctamente.", null, 200);
    }
}
