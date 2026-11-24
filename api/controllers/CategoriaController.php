<?php
// api/controllers/CategoriaController.php

require_once __DIR__ . '/../models/CategoriaModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class CategoriaController
{
    private CategoriaModel $modelo;

    public function __construct()
    {
        $this->modelo = new CategoriaModel();
    }

    public function listar(): void
    {
        $categorias = $this->modelo->listar();
        ResponseHelper::exito('Listado de categorías obtenido correctamente.', $categorias, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_categoria');
        $categoria = $this->modelo->obtenerPorId($id);

        if (!$categoria) {
            ResponseHelper::error("No se encontró la categoría con ID {$id}.", 404);
        }

        ResponseHelper::exito('Categoría encontrada.', $categoria, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (!isset($datos['nombre']) || trim((string) $datos['nombre']) === '') {
            ResponseHelper::error('El campo "nombre" es obligatorio.', 400);
        }

        $nombre = trim((string) $datos['nombre']);
        if (mb_strlen($nombre) > 80) {
            ResponseHelper::error('El campo "nombre" no debe superar los 80 caracteres.', 400);
        }

        if ($this->modelo->existeNombre($nombre)) {
            ResponseHelper::error("Ya existe una categoría con el nombre '{$nombre}'.", 400);
        }

        if (isset($datos['activo']) && !in_array((int) $datos['activo'], [0, 1], true)) {
            ResponseHelper::error('El campo "activo" debe ser 0 o 1.', 400);
        }

        $datos['nombre'] = $nombre;
        $creada = $this->modelo->crear($datos);
        ResponseHelper::exito('Categoría creada correctamente.', $creada, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_categoria');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró la categoría con ID {$id}.", 404);
        }

        if (isset($datos['nombre'])) {
            $nombre = trim((string) $datos['nombre']);
            if ($nombre === '' || mb_strlen($nombre) > 80) {
                ResponseHelper::error('El campo "nombre" debe tener entre 1 y 80 caracteres.', 400);
            }
            if ($this->modelo->existeNombre($nombre, $id)) {
                ResponseHelper::error("Ya existe otra categoría con el nombre '{$nombre}'.", 400);
            }
            $datos['nombre'] = $nombre;
        }

        if (isset($datos['activo']) && !in_array((int) $datos['activo'], [0, 1], true)) {
            ResponseHelper::error('El campo "activo" debe ser 0 o 1.', 400);
        }

        $actualizada = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Categoría actualizada correctamente.', $actualizada, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_categoria');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró la categoría con ID {$id}.", 404);
        }

        if ($this->modelo->tieneProductosAsociados($id)) {
            ResponseHelper::error('No se puede eliminar la categoría porque tiene productos registrados.', 400);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Categoría con ID {$id} eliminada correctamente.", null, 200);
    }
}
