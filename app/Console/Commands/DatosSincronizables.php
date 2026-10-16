<?php

namespace App\Console\Commands;

/**
 * Tablas que viajan del entorno local al servidor de pruebas con
 * datos:exportar / datos:importar, en orden de dependencias (padres primero).
 *
 * Quedan fuera a propósito:
 *  - Seguridad: users, roles_usuario, user_permissions, user_permisos_granulares,
 *    area_user, sessions, password_reset_tokens.
 *  - Historias clínicas: historias_clinicas, muestras, respuestas.
 *  - Notas de pago: notas_pago, nota_pago_conceptos, nota_pago_historia_clinica.
 *  - Infraestructura: migrations, cache, jobs, failed_jobs, job_batches.
 */
final class DatosSincronizables
{
    public const TABLAS = [
        'estados',
        'municipios',
        'datos_generales',
        'parametros_operacion',
        'areas',
        'analistas',
        'categorias',
        'metodos',
        'normas',
        'productos',
        'pruebas',
        'especies',
        'razas',
        'funcion_zootecnicas',
        'especie_funcion_zootecnica',
        'preguntas',
        'tipo_muestras',
        'servicios',
        'norma_servicio',
        'servicio_tipo_muestra',
        'propietarios',
        'direcciones',
        'explotaciones',
        'clientes',
    ];

    public static function directorio(): string
    {
        return database_path('data/sync');
    }
}
