<?php

/*
|--------------------------------------------------------------------------
| Módulos y acciones controlables por usuario
|--------------------------------------------------------------------------
|
| La clave de cada módulo coincide con el prefijo del nombre de ruta
| (ej. 'estados' => estados.index, estados.create, ...). Gracias a eso el
| middleware VerificarPermiso deduce el módulo y la acción directamente del
| nombre de la ruta, sin tener que anotar cada una a mano.
|
*/

return [
    'modulos' => [
        'historias-clinicas' => 'Historias Clínicas',
        'cuestionario' => 'Cuestionario',
        'propietarios' => 'Propietarios',
        'areas' => 'Áreas',
        'datos-generales' => 'Datos Generales',
        'parametros-operacion' => 'Parámetros de Operación',
        'mantenimiento-base-datos' => 'Mantenimiento de Base de Datos',
        'estados' => 'Estados',
        'municipios' => 'Municipios',
        'especies' => 'Especies',
        'razas' => 'Razas',
        'pruebas' => 'Pruebas',
        'funcion-zootecnicas' => 'Funciones Zootécnicas',
        'servicios' => 'Servicios',
        'users' => 'Usuarios',
    ],

    'acciones' => [
        'ver' => 'Ver',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'eliminar' => 'Eliminar',
    ],

    // Nombre de ruta (sufijo tras el módulo) => acción requerida.
    'acciones_por_ruta' => [
        'index' => 'ver',
        'show' => 'ver',
        'create' => 'crear',
        'store' => 'crear',
        'edit' => 'editar',
        'update' => 'editar',
        'destroy' => 'eliminar',
    ],

    'dias' => [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ],
];
