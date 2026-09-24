<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MantenimientoBaseDatosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Configuracion/MantenimientoBaseDatos/Index');
    }
}
