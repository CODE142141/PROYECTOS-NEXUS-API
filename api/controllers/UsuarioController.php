<?php
// api/controllers/UsuarioController.php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/ResponseHelper.php';

class UsuarioController
{
    private UsuarioModel $modelo;

    public function __construct()
    {
        $this->modelo = new UsuarioModel();
    }

    public function listar(): void
    {
        $usuarios = $this->modelo->listar();
        ResponseHelper::exito('Listado de usuarios obtenido correctamente.', $usuarios, 200);
    }

    public function obtenerPorId($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_usuario');
        $usuario = $this->modelo->obtenerPorId($id);

        if (!$usuario) {
            ResponseHelper::error("No se encontró el usuario con ID {$id}.", 404);
        }

        ResponseHelper::exito('Usuario encontrado.', $usuario, 200);
    }

    public function crear(): void
    {
        $datos = ResponseHelper::obtenerCuerpoJson();

        $camposObligatorios = ['id_rol', 'nombre', 'email', 'clave'];
        foreach ($camposObligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                ResponseHelper::error("El campo '{$campo}' es obligatorio.", 400);
            }
        }

        $idRol = ResponseHelper::validarId($datos['id_rol'], 'id_rol');
        if (!$this->modelo->existeRol($idRol)) {
            ResponseHelper::error("El rol especificado (id_rol: {$idRol}) no existe.", 400);
        }

        $email = trim((string) $datos['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::error('El formato del correo electrónico no es válido.', 400);
        }

        if ($this->modelo->existeEmail($email)) {
            ResponseHelper::error("El correo '{$email}' ya se encuentra registrado.", 400);
        }

        if (strlen((string) $datos['clave']) < 6) {
            ResponseHelper::error('La contraseña ("clave") debe tener al menos 6 caracteres.', 400);
        }

        $datos['id_rol'] = $idRol;
        $datos['nombre'] = trim((string) $datos['nombre']);
        $datos['email']  = $email;

        $creado = $this->modelo->crear($datos);
        ResponseHelper::exito('Usuario creado correctamente.', $creado, 201);
    }

    public function actualizar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_usuario');
        $datos = ResponseHelper::obtenerCuerpoJson();

        if (empty($datos)) {
            ResponseHelper::error('Debe enviar al menos un campo para actualizar.', 400);
        }

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el usuario con ID {$id}.", 404);
        }

        if (isset($datos['id_rol'])) {
            $idRol = ResponseHelper::validarId($datos['id_rol'], 'id_rol');
            if (!$this->modelo->existeRol($idRol)) {
                ResponseHelper::error("El rol especificado (id_rol: {$idRol}) no existe.", 400);
            }
            $datos['id_rol'] = $idRol;
        }

        if (isset($datos['email'])) {
            $email = trim((string) $datos['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ResponseHelper::error('El formato del correo electrónico no es válido.', 400);
            }
            if ($this->modelo->existeEmail($email, $id)) {
                ResponseHelper::error("El correo '{$email}' ya pertenece a otro usuario.", 400);
            }
            $datos['email'] = $email;
        }

        if (isset($datos['clave']) && trim((string) $datos['clave']) !== '' && strlen((string) $datos['clave']) < 6) {
            ResponseHelper::error('La nueva contraseña ("clave") debe tener al menos 6 caracteres.', 400);
        }

        $actualizado = $this->modelo->actualizar($id, $datos);
        ResponseHelper::exito('Usuario actualizado correctamente.', $actualizado, 200);
    }

    public function eliminar($idParam): void
    {
        $id = ResponseHelper::validarId($idParam, 'id_usuario');

        if (!$this->modelo->obtenerPorId($id)) {
            ResponseHelper::error("No se encontró el usuario con ID {$id}.", 404);
        }

        if ($this->modelo->tienePedidosAsociados($id)) {
            ResponseHelper::error('No se puede eliminar el usuario porque tiene pedidos registrados a su nombre.', 400);
        }

        $this->modelo->eliminar($id);
        ResponseHelper::exito("Usuario con ID {$id} eliminado correctamente.", null, 200);
    }
}
