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
        'metodos' => 'Métodos',
        'tipos-muestra' => 'Tipos de Muestra',
        'servicios' => 'Análisis',
        'productos' => 'Productos',
        'clientes' => 'Clientes',
        'users' => 'Usuarios',
        'roles' => 'Roles',
    ],

    /*
    | Secciones del formulario de usuarios. Siguen el orden y los grupos del
    | menú lateral (AuthenticatedLayout.jsx) para que "Funciones permitidas"
    | se lea igual que el menú. 'modulos' son claves de 'modulos' arriba y
    | 'grupos_granulares' claves de config/permisos_granulares.php.
    */
    'secciones' => [
        'historias-clinicas' => [
            'etiqueta' => 'Historias Clínicas',
            'modulos' => ['historias-clinicas', 'cuestionario'],
            'grupos_granulares' => ['historias-clinicas'],
        ],
        'notas-pago' => [
            'etiqueta' => 'Notas de pago',
            'modulos' => ['productos', 'clientes'],
            'grupos_granulares' => ['facturacion'],
        ],
        'diagnosticos' => [
            'etiqueta' => 'Diagnósticos',
            'modulos' => [],
            'grupos_granulares' => ['diagnosticos'],
        ],
        'servicios' => [
            'etiqueta' => 'Análisis',
            'modulos' => ['servicios'],
            'grupos_granulares' => [],
        ],
        'reportes' => [
            'etiqueta' => 'Reportes',
            'modulos' => [],
            'grupos_granulares' => ['reportes'],
        ],
        'catalogos' => [
            'etiqueta' => 'Catálogos',
            'modulos' => [
                'propietarios', 'municipios', 'estados', 'especies',
                'razas', 'pruebas', 'funcion-zootecnicas', 'metodos', 'tipos-muestra',
            ],
            'grupos_granulares' => [],
        ],
        'configuracion-general' => [
            'etiqueta' => 'Configuración General',
            'modulos' => ['datos-generales', 'mantenimiento-base-datos', 'parametros-operacion', 'areas'],
            'grupos_granulares' => ['configuracion-general'],
        ],
        'seguridad' => [
            'etiqueta' => 'Seguridad',
            'modulos' => ['users', 'roles'],
            'grupos_granulares' => [],
        ],
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
