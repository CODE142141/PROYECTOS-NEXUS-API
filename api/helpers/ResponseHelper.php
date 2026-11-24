<?php
// api/helpers/ResponseHelper.php

class ResponseHelper
{
    public static function json($payload, int $codigoHttp = 200): void
    {
        http_response_code($codigoHttp);
        $datosUtf8 = self::normalizarUtf8($payload);
        echo json_encode($datosUtf8, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function exito(string $mensaje, $datos = null, int $codigoHttp = 200): void
    {
        $respuesta = [
            'ok'      => true,
            'mensaje' => $mensaje,
        ];
        if ($datos !== null) {
            $respuesta['datos'] = $datos;
        }
        self::json($respuesta, $codigoHttp);
    }

    public static function error(string $mensaje, int $codigoHttp = 400, $errores = null): void
    {
        $respuesta = [
            'ok'      => false,
            'mensaje' => $mensaje,
        ];
        if ($errores !== null) {
            $respuesta['errores'] = $errores;
        }
        self::json($respuesta, $codigoHttp);
    }

    public static function obtenerCuerpoJson(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return !empty($_POST) ? $_POST : [];
        }

        $decodificado = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodificado)) {
            self::error('El cuerpo de la solicitud no contiene un JSON válido.', 400);
        }

        return $decodificado;
    }

    public static function validarId($id, string $nombreCampo = 'ID'): int
    {
        if ($id === null || filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
            self::error("El parámetro {$nombreCampo} debe ser un número entero positivo válido.", 400);
        }
        return (int) $id;
    }

    public static function normalizarUtf8($dato)
    {
        if (is_array($dato)) {
            foreach ($dato as $clave => $valor) {
                $dato[$clave] = self::normalizarUtf8($valor);
            }
            return $dato;
        }

        if (is_string($dato) && !mb_check_encoding($dato, 'UTF-8')) {
            return mb_convert_encoding($dato, 'UTF-8', 'Windows-1252');
        }

        return $dato;
    }

    public static function aSqlServer(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }
        return mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');
    }
}
