<?php

namespace App\Support;

/**
 * Normaliza la matriz módulo × acción (config/modulos.php) y los permisos
 * granulares (config/permisos_granulares.php) tal como llegan del formulario
 * o se guardan en la base de datos. Lo comparten usuarios y roles.
 */
class Permisos
{
    /**
     * Matriz completa (todos los módulos y acciones declarados, en bool) a
     * partir de una matriz parcial.
     *
     * @param  array<string, array<string, mixed>>  $parcial
     * @return array<string, array<string, bool>>
     */
    public static function matrizCompleta(array $parcial): array
    {
        $matriz = [];

        foreach (array_keys(config('modulos.modulos')) as $modulo) {
            foreach (array_keys(config('modulos.acciones')) as $accion) {
                $matriz[$modulo][$accion] = filter_var(
                    $parcial[$modulo][$accion] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        return $matriz;
    }

    /**
     * Sólo las filas con alguna acción concedida, ignorando módulos o
     * acciones que no estén declarados.
     *
     * @param  array<string, array<string, mixed>>  $entrada
     * @return array<string, array<string, bool>>
     */
    public static function matrizConcedida(array $entrada): array
    {
        return array_filter(
            self::matrizCompleta($entrada),
            fn (array $fila) => in_array(true, $fila, true)
        );
    }

    /**
     * Claves declaradas en config/permisos_granulares.php, sin importar el
     * grupo al que pertenezcan.
     *
     * @return array<int, string>
     */
    public static function clavesGranulares(): array
    {
        return collect(config('permisos_granulares.grupos', []))
            ->flatMap(fn (array $grupo) => array_keys($grupo['permisos']))
            ->all();
    }

    /**
     * Claves declaradas que vienen marcadas en un mapa clave => bool.
     *
     * @param  array<string, mixed>  $entrada
     * @return array<int, string>
     */
    public static function granularesConcedidos(array $entrada): array
    {
        return collect(self::clavesGranulares())
            ->filter(fn (string $clave) => filter_var($entrada[$clave] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->values()
            ->all();
    }

    /**
     * Mapa clave => bool de todas las claves declaradas a partir de la lista
     * de claves concedidas.
     *
     * @param  array<int, string>  $concedidas
     * @return array<string, bool>
     */
    public static function mapaGranular(array $concedidas): array
    {
        return collect(self::clavesGranulares())
            ->mapWithKeys(fn (string $clave) => [$clave => in_array($clave, $concedidas, true)])
            ->all();
    }
}
