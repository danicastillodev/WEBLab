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
            ],
            'flash' => [
                'success' => session('success'),
                'numero_caso' => session('numero_caso'),
                'historia_id' => session('historia_id'),
            ],
        ];
    }
}
