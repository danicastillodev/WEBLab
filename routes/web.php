<?php

use App\Http\Controllers\AreasController;
use App\Http\Controllers\CuestionarioController;
use App\Http\Controllers\DatosGeneralesController;
use App\Http\Controllers\EspeciesController;
use App\Http\Controllers\EstadosController;
use App\Http\Controllers\FuncionZootecnicasController;
use App\Http\Controllers\HistoriasClinicasController;
use App\Http\Controllers\MantenimientoBaseDatosController;
use App\Http\Controllers\MunicipiosController;
use App\Http\Controllers\ParametrosOperacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropietariosController;
use App\Http\Controllers\PruebasController;
use App\Http\Controllers\RazasController;
use App\Http\Controllers\ServiciosController;
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

    Route::resource('users', UserController::class);

    Route::resource('areas', AreasController::class)->except(['show']);

    Route::get('configuracion/datos-generales', [DatosGeneralesController::class, 'edit'])->name('datos-generales.edit');
    Route::put('configuracion/datos-generales', [DatosGeneralesController::class, 'update'])->name('datos-generales.update');

    Route::get('configuracion/parametros-operacion', [ParametrosOperacionController::class, 'edit'])->name('parametros-operacion.edit');
    Route::put('configuracion/parametros-operacion', [ParametrosOperacionController::class, 'update'])->name('parametros-operacion.update');

    Route::get('configuracion/mantenimiento-base-datos', [MantenimientoBaseDatosController::class, 'index'])->name('mantenimiento-base-datos.index');

    // Alta y baja de miembros del área desde el propio catálogo de áreas.
    Route::post('areas/{area}/usuarios', [AreasController::class, 'agregarUsuario'])
        ->name('areas.usuarios.store');
    Route::delete('areas/{area}/usuarios/{user}', [AreasController::class, 'quitarUsuario'])
        ->name('areas.usuarios.destroy');

    Route::resource('estados', EstadosController::class)->except(['show']);
    Route::resource('propietarios', PropietariosController::class)->except(['show']);
    Route::resource('municipios', MunicipiosController::class)->except(['show']);
    // Str::singular('especies') da 'especy', que no coincide con el argumento
    // $especie del controlador y rompe el model binding implícito.
    Route::resource('especies', EspeciesController::class)
        ->except(['show'])
        ->parameters(['especies' => 'especie']);
    Route::resource('razas', RazasController::class)->except(['show']);
    Route::resource('pruebas', PruebasController::class)->except(['show']);
    Route::resource('funcion-zootecnicas', FuncionZootecnicasController::class)->except(['show']);
    Route::resource('servicios', ServiciosController::class)->except(['show']);

    // El catálogo de preguntas se expone bajo 'cuestionario' pero el modelo es Pregunta.
    Route::resource('cuestionario', CuestionarioController::class)
        ->except(['show'])
        ->parameters(['cuestionario' => 'pregunta']);

    // Altas, ediciones y bajas de registros relacionados que se hacen desde los
    // modales de la historia clínica (vía axios, sin recargar la página).
    Route::controller(HistoriasClinicasController::class)
        ->prefix('historias-clinicas')
        ->name('historias-clinicas.')
        ->group(function () {
            Route::post('propietario', 'storePropietario')->name('store-propietario');
            Route::patch('propietario/{propietario}', 'updatePropietario')->name('update-propietario');
            Route::delete('propietario/{propietario}', 'destroyPropietario')->name('destroy-propietario');

            Route::post('direccion', 'storeDireccion')->name('store-direccion');
            Route::patch('direccion/{direccion}', 'updateDireccion')->name('update-direccion');
            Route::delete('direccion/{direccion}', 'destroyDireccion')->name('destroy-direccion');

            Route::post('explotacion', 'storeExplotacion')->name('store-explotacion');
            Route::patch('explotacion/{explotacion}', 'updateExplotacion')->name('update-explotacion');
            Route::delete('explotacion/{explotacion}', 'destroyExplotacion')->name('destroy-explotacion');

            Route::post('especie', 'storeEspecie')->name('store-especie');
            Route::patch('especie/{especie}', 'updateEspecie')->name('update-especie');
            Route::delete('especie/{especie}', 'destroyEspecie')->name('destroy-especie');

            Route::post('raza', 'storeRaza')->name('store-raza');
            Route::patch('raza/{raza}', 'updateRaza')->name('update-raza');
            Route::delete('raza/{raza}', 'destroyRaza')->name('destroy-raza');

            Route::post('funcion-zootecnica', 'storeFuncionZootecnica')->name('store-funcion-zootecnica');
            Route::patch('funcion-zootecnica/{funcion_zootecnica}', 'updateFuncionZootecnica')->name('update-funcion-zootecnica');
            Route::delete('funcion-zootecnica/{funcion_zootecnica}', 'destroyFuncionZootecnica')->name('destroy-funcion-zootecnica');

            Route::post('prueba', 'storePrueba')->name('store-prueba');
            Route::patch('prueba/{prueba}', 'updatePrueba')->name('update-prueba');
            Route::delete('prueba/{prueba}', 'destroyPrueba')->name('destroy-prueba');

            Route::post('tipo-muestra', 'storeTipoMuestra')->name('store-tipo-muestra');
            Route::patch('tipo-muestra/{tipo_muestra}', 'updateTipoMuestra')->name('update-tipo-muestra');
            Route::delete('tipo-muestra/{tipo_muestra}', 'destroyTipoMuestra')->name('destroy-tipo-muestra');

            Route::get('ticket/{historia_clinica}', 'ticket')->name('ticket');
            Route::patch('{historia_clinica}/cancelar', 'cancel')->name('cancelar');
        });

    // Str::singular('historias-clinicas') da 'historias_clinica', que no coincide
    // con el argumento $historiaClinica del controlador.
    Route::resource('historias-clinicas', HistoriasClinicasController::class)
        ->parameters(['historias-clinicas' => 'historia_clinica']);
});

require __DIR__.'/auth.php';
