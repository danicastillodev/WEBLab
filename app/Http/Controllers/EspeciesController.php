<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class EspeciesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Especies/Index');
    }
}
