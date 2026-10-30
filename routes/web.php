<?php

use App\Http\Controllers\AreasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\CuestionarioController;
use App\Http\Controllers\DatosGeneralesController;
use App\Http\Controllers\DatosPersonalesController;
use App\Http\Controllers\DiagnosticosController;
use App\Http\Controllers\EspeciesController;
use App\Http\Controllers\EstadosController;
use App\Http\Controllers\FuncionZootecnicasController;
use App\Http\Controllers\HistoriasClinicasController;
use App\Http\Controllers\MantenimientoBaseDatosController;
use App\Http\Controllers\MetodosController;
use App\Http\Controllers\MunicipiosController;
use App\Http\Controllers\NotasPagoController;
use App\Http\Controllers\ParametrosOperacionController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropietariosController;
use App\Http\Controllers\PruebasController;
use App\Http\Controllers\RazasController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ServiciosController;
use App\Http\Controllers\TiposMuestraController;
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
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::get('/profile/avatar/{user}', [ProfileController::class, 'showAvatar'])->name('profile.avatar.show');

    Route::get('seguridad/datos-personales', [DatosPersonalesController::class, 'edit'])->name('datos-personales.edit');
    Route::put('seguridad/datos-personales', [DatosPersonalesController::class, 'update'])->name('datos-personales.update');
    Route::put('seguridad/datos-personales/contrasena', [DatosPersonalesController::class, 'updatePassword'])->name('datos-personales.update-password');

    Route::resource('users', UserController::class);
    Route::patch('users/{user}/borrar-contrasena', [UserController::class, 'borrarContrasena'])
        ->name('users.borrar-contrasena');
    // Str::singular('roles') da 'role', que no coincide con el argumento $rol.
    Route::resource('roles', RolController::class)
        ->except(['show'])
        ->parameters(['roles' => 'rol']);

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
    Route::resource('metodos', MetodosController::class)->except(['show']);
    Route::resource('tipos-muestra', TiposMuestraController::class)
        ->except(['show'])
        ->parameters(['tipos-muestra' => 'tipo_muestra']);
    // Alta rápida de catálogos (método, categoría, área...) desde el formulario del análisis.
    Route::post('servicios/catalogo/{catalogo}', [ServiciosController::class, 'storeCatalogo'])
        ->name('servicios.store-catalogo');
    // Modificar y eliminar desde el formulario; el permiso se revisa en el controlador.
    Route::patch('servicios/catalogo/{catalogo}/{id}', [ServiciosController::class, 'updateCatalogo'])
        ->whereNumber('id')
        ->name('servicios.update-catalogo');
    Route::delete('servicios/catalogo/{catalogo}/{id}', [ServiciosController::class, 'destroyCatalogo'])
        ->whereNumber('id')
        ->name('servicios.destroy-catalogo');
    // Activar/desactivar; el permiso se revisa en el controlador.
    Route::patch('servicios/{servicio}/activo', [ServiciosController::class, 'updateActivo'])
        ->name('servicios.update-activo');
    // Los análisis no se eliminan, sólo se desactivan.
    Route::resource('servicios', ServiciosController::class)->except(['show', 'destroy']);
    Route::resource('productos', ProductosController::class)->except(['show', 'destroy']);
    Route::resource('clientes', ClientesController::class)->except(['show']);

    Route::controller(NotasPagoController::class)
        ->prefix('notas-pago')
        ->name('notas-pago.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('nueva', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{notaPago}/imprimir', 'imprimir')->whereNumber('notaPago')->name('imprimir');
            Route::get('{notaPago}/editar', 'edit')->whereNumber('notaPago')->name('edit');
            Route::put('{notaPago}', 'update')->whereNumber('notaPago')->name('update');
            Route::patch('{notaPago}/cancelar', 'cancelar')->whereNumber('notaPago')->name('cancelar');
            Route::delete('{notaPago}', 'destroy')->whereNumber('notaPago')->name('destroy');
            Route::post('clientes', 'storeCliente')->name('store-cliente');
            Route::patch('clientes/{cliente}', 'updateCliente')->name('update-cliente');
            Route::delete('clientes/{cliente}', 'destroyCliente')->name('destroy-cliente');
            Route::get('historias-clinicas', 'buscarHistorias')->name('buscar-historias');
            Route::get('historias-clinicas/conceptos', 'conceptosDeHistorias')->name('conceptos-de-historias');
        });

    // 'diagnosticos' no es un módulo de config/modulos.php: el permiso
    // granular se revisa en el controlador.
    Route::controller(DiagnosticosController::class)
        ->prefix('diagnosticos')
        ->name('diagnosticos.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('analisis/{muestra}', 'edit')->whereNumber('muestra')->name('edit');
            Route::put('analisis/{muestra}', 'guardar')->whereNumber('muestra')->name('guardar');
            Route::patch('analisis/{muestra}/cancelar', 'cancelar')->whereNumber('muestra')->name('cancelar');
        });

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

            Route::get('ticket/{historia_clinica}', 'ticket')->name('ticket');
            Route::patch('{historia_clinica}/cancelar', 'cancel')->name('cancelar');
        });

    // Str::singular('historias-clinicas') da 'historias_clinica', que no coincide
    // con el argumento $historiaClinica del controlador.
    Route::resource('historias-clinicas', HistoriasClinicasController::class)
        ->parameters(['historias-clinicas' => 'historia_clinica']);
});

require __DIR__.'/auth.php';
