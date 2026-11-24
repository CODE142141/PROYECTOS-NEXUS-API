<?php
// api/config/EnvLoader.php

class EnvLoader
{
    public static function cargar(string $rutaEnv): void
    {
        if (!file_exists($rutaEnv)) {
            return;
        }

        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return;
        }

        foreach ($lineas as $linea) {
            $lineaLimpia = trim($linea);
            if ($lineaLimpia === '' || str_starts_with($lineaLimpia, '#')) {
                continue;
            }

            $posicionIgual = strpos($lineaLimpia, '=');
            if ($posicionIgual === false) {
                continue;
            }

            $clave = trim(substr($lineaLimpia, 0, $posicionIgual));
            $valor = trim(substr($lineaLimpia, $posicionIgual + 1));

            if (
                (str_starts_with($valor, '"') && str_ends_with($valor, '"')) ||
                (str_starts_with($valor, "'") && str_ends_with($valor, "'"))
            ) {
                $valor = substr($valor, 1, -1);
            }

            $_ENV[$clave] = $valor;
            putenv("$clave=$valor");
        }
    }

    public static function obtener(string $clave, string $porDefecto = ''): string
    {
        if (isset($_ENV[$clave]) && $_ENV[$clave] !== '') {
            return (string) $_ENV[$clave];
        }
        $valorEnv = getenv($clave);
        return ($valorEnv !== false && $valorEnv !== '') ? (string) $valorEnv : $porDefecto;
    }
}
