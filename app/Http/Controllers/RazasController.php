<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class RazasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Razas/Index');
    }
}
