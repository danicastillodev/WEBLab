<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deduce el módulo y la acción a partir del nombre de la ruta y comprueba que
 * el usuario tenga el permiso correspondiente.
 *
 *   estados.index                        => modulo 'estados',            accion 'ver'
 *   razas.destroy                        => modulo 'razas',              accion 'eliminar'
 *   historias-clinicas.store-propietario => modulo 'historias-clinicas', accion 'crear'
 *
 * Las rutas cuyo prefijo no sea un módulo declarado en config/modulos.php
 * (perfil, dashboard, auth...) pasan sin comprobación.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next): Response
    {
        $nombre = $request->route()?->getName();
        $user = $request->user();

        if (! $nombre || ! $user) {
            return $next($request);
        }

        [$modulo, $accion] = $this->resolver($nombre);

        if ($modulo === null || $accion === null) {
            return $next($request);
        }

        if (! $user->puede($modulo, $accion)) {
            abort(403, 'No tiene permiso para realizar esta acción.');
        }

        return $next($request);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolver(string $nombre): array
    {
        $posicion = strrpos($nombre, '.');

        if ($posicion === false) {
            return [null, null];
        }

        $modulo = substr($nombre, 0, $posicion);
        $sufijo = substr($nombre, $posicion + 1);

        if (! array_key_exists($modulo, config('modulos.modulos'))) {
            return [null, null];
        }

        $accion = config('modulos.acciones_por_ruta')[$sufijo] ?? null;

        // Rutas auxiliares tipo 'store-propietario' que crean registros
        // relacionados desde un modal.
        if ($accion === null && str_starts_with($sufijo, 'store-')) {
            $accion = 'crear';
        }

        return [$modulo, $accion];
    }
}
