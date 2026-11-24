<?php
// api/controllers/RolController.php

require_once __DIR__ . '/../models/RolModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class RolController
{
    private RolModel $modelo;

    public function __construct()
    {
        $this->modelo = new RolModel();
    }

    public function listar(): void
    {
        $roles = $this->modelo->listar();
        ResponseHelper::exito('Listado de roles obtenido correctamente.', $roles, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_rol');
        $rol = $this->modelo->obtenerPorId($id);

        if (!$rol) {
            ResponseHelper::error("No se encontró el rol con ID {$id}.", 404);
        }

        ResponseHelper::exito('Rol encontrado.', $rol, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (!isset($datos['nombre']) || trim((string) $datos['nombre']) === '') {
            ResponseHelper::error('El campo "nombre" es obligatorio.', 400);
        }

        $nombre = trim((string) $datos['nombre']);
        if (mb_strlen($nombre) > 50) {
            ResponseHelper::error('El campo "nombre" no debe superar los 50 caracteres.', 400);
        }

        if ($this->modelo->existeNombre($nombre)) {
            ResponseHelper::error("Ya existe un rol registrado con el nombre '{$nombre}'.", 400);
        }

        if (isset($datos['estado']) && !in_array((int) $datos['estado'], [0, 1], true)) {
            ResponseHelper::error('El campo "estado" debe ser 0 (inactivo) o 1 (activo).', 400);
        }

        $datos['nombre'] = $nombre;
        $creado = $this->modelo->crear($datos);
        ResponseHelper::exito('Rol creado correctamente.', $creado, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_rol');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el rol con ID {$id}.", 404);
        }

        if (isset($datos['nombre'])) {
            $nombre = trim((string) $datos['nombre']);
            if ($nombre === '' || mb_strlen($nombre) > 50) {
                ResponseHelper::error('El campo "nombre" debe tener entre 1 y 50 caracteres.', 400);
            }
            if ($this->modelo->existeNombre($nombre, $id)) {
                ResponseHelper::error("Ya existe otro rol con el nombre '{$nombre}'.", 400);
            }
            $datos['nombre'] = $nombre;
        }

        if (isset($datos['estado']) && !in_array((int) $datos['estado'], [0, 1], true)) {
            ResponseHelper::error('El campo "estado" debe ser 0 o 1.', 400);
        }

        $actualizado = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Rol actualizado correctamente.', $actualizado, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_rol');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el rol con ID {$id}.", 404);
        }

        if ($this->modelo->tieneUsuariosAsociados($id)) {
            ResponseHelper::error('No se puede eliminar el rol porque tiene usuarios asignados.', 400);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Rol con ID {$id} eliminado correctamente.", null, 200);
    }
}
