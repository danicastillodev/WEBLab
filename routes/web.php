<?php

use App\Http\Controllers\EstadosController;
use App\Http\Controllers\EspeciesController;
use App\Http\Controllers\HistoriasClinicasController;
use App\Http\Controllers\MunicipiosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropietariosController;
use App\Http\Controllers\PruebasController;
use App\Http\Controllers\RazasController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);

    Route::resource('estados', EstadosController::class)->except(['show']);
    Route::resource('propietarios', PropietariosController::class)->except(['show']);
    Route::resource('municipios', MunicipiosController::class)->except(['show']);
    Route::resource('especies', EspeciesController::class)->except(['show']);
    Route::resource('razas', RazasController::class)->except(['show']);
    Route::resource('pruebas', PruebasController::class)->except(['show']);

    Route::post('historias-clinicas/propietario', [HistoriasClinicasController::class, 'storePropietario'])->name('historias-clinicas.store-propietario');
    Route::post('historias-clinicas/direccion', [HistoriasClinicasController::class, 'storeDireccion'])->name('historias-clinicas.store-direccion');
    Route::post('historias-clinicas/especie', [HistoriasClinicasController::class, 'storeEspecie'])->name('historias-clinicas.store-especie');
    Route::post('historias-clinicas/raza', [HistoriasClinicasController::class, 'storeRaza'])->name('historias-clinicas.store-raza');
    Route::post('historias-clinicas/tipo-muestra', [HistoriasClinicasController::class, 'storeTipoMuestra'])->name('historias-clinicas.store-tipo-muestra');
    Route::post('historias-clinicas/prueba', [HistoriasClinicasController::class, 'storePrueba'])->name('historias-clinicas.store-prueba');
    Route::resource('historias-clinicas', HistoriasClinicasController::class)->except(['show']);
});

require __DIR__.'/auth.php';
