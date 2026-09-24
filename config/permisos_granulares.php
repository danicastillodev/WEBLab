<?php

/*
|--------------------------------------------------------------------------
| Permisos granulares por usuario
|--------------------------------------------------------------------------
|
| A diferencia de config/modulos.php (matriz módulo × ver/crear/editar/
| eliminar), aquí se declaran permisos puntuales que no encajan en ese
| patrón CRUD genérico. Cada clave es única y se guarda como una fila en
| user_permisos_granulares cuando está marcada.
|
| Sólo se listan los permisos que NO tienen ya un equivalente en
| config/modulos.php (por ejemplo, "Agregar Usuarios" ya existe como la
| acción "crear" del módulo "users", así que no se repite aquí).
|
*/

return [
    'grupos' => [
        'facturacion' => [
            'etiqueta' => 'Facturación',
            'permisos' => [
                'facturacion.agregar_facturas' => 'Agregar facturas',
                'facturacion.modificar_facturas' => 'Modificar facturas',
                'facturacion.agregar_productos' => 'Agregar productos',
                'facturacion.modificar_productos' => 'Modificar productos',
                'facturacion.agregar_notas_abono' => 'Agregar Notas de Abono',
                'facturacion.modificar_notas_abono' => 'Modificar Notas de Abono',
                'facturacion.cancelar_factura' => 'Cancelar una factura',
                'facturacion.cancelar_nota_abono' => 'Cancelar una nota de abono',
                'facturacion.modificar_iva' => 'Permitir modificar IVA',
                'facturacion.refacturar_historias_clinicas' => 'Refacturar historias clínicas',
            ],
        ],

        'diagnosticos' => [
            'etiqueta' => 'Diagnósticos',
            'permisos' => [
                'diagnosticos.agregar' => 'Agregar un diagnóstico',
                'diagnosticos.modificar' => 'Modificar un diagnóstico',
                'diagnosticos.emitir' => 'Emitir un diagnóstico',
                'diagnosticos.consultar_de_otros' => 'Consultar diagnósticos creados por otros usuarios',
                'diagnosticos.modificar_emitidos' => 'Modificar diagnósticos emitidos',
                'diagnosticos.cancelar' => 'Cancelar diagnósticos',
            ],
        ],

        'historias-clinicas' => [
            'etiqueta' => 'Historias Clínicas',
            'permisos' => [
                'historias-clinicas.imprimir_resultado' => 'Imprimir resultado',
                'historias-clinicas.reimprimir_resultado' => 'Reimprimir resultados',
            ],
        ],

        'configuracion-general' => [
            'etiqueta' => 'Configuración General',
            'permisos' => [
                'configuracion-general.folio_automatico_facturas' => 'Configurar generación de folio automático en facturas',
                'configuracion-general.porcentaje_iva_facturas' => 'Configurar el porcentaje de IVA aplicado a facturas',
            ],
        ],

        'reportes' => [
            'etiqueta' => 'Reportes',
            'permisos' => [
                'reportes.diagnosticos' => 'Acceso Reporte de diagnósticos',
                'reportes.analisis_x_especie' => 'Acceso Reporte de Análisis X Especie',
                'reportes.muestras_trabajadas_x_especie' => 'Acceso Reporte de Muestras Trabajadas X Especie',
                'reportes.accesos_sospechosos' => 'Acceso Reporte Accesos Sospechosos',
                'reportes.auditoria_movimientos' => 'Acceso Reporte Auditoría de Movimientos',
                'reportes.notificacion_b_agonistas' => 'Acceso Reporte Notificación de Res. de B-Agonistas',
                'reportes.global' => 'Acceso Reporte Global',
                'reportes.estado_cuenta_cliente' => 'Acceso Reporte de Estado de cuenta por cliente',
                'reportes.estados_movimientos_facturacion' => 'Acceso Reporte de Estados Movimientos de facturación',
                'reportes.volumen_trabajo' => 'Acceso Reporte de volumen de trabajo',
                'reportes.muestras_casos_no_facturados' => 'Acceso Reporte Muestras / Casos no facturados',
            ],
        ],
    ],
];
