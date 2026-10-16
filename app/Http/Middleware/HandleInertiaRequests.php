<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                // Matriz usada por el sidebar para ocultar los módulos a los
                // que el usuario no tiene acceso de lectura.
                'permisos' => $user?->matrizDePermisos() ?? [],
                // Permisos puntuales (ver config/permisos_granulares.php) que
                // no encajan en la matriz módulo × acción anterior.
                'permisosGranulares' => $user?->matrizDePermisosGranulares() ?? [],
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'numero_caso' => session('numero_caso'),
                'historia_id' => session('historia_id'),
            ],
        ];
    }
}
