import EstadoBadge from '@/Components/EstadoBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

// Etiquetas de "impreso"; el valor guardado es el de la columna del mismo nombre.
const IMPRESOS = {
    si: 'Sí',
    no: 'No',
    parcial: 'Parcial',
};

/** "2026-08-25" (o un ISO completo) → "25/08/2026", sin desfase de zona horaria. */
function formatFecha(valor) {
    if (!valor) return '—';
    const [y, m, d] = String(valor).slice(0, 10).split('-');
    return y && m && d ? `${d}/${m}/${y}` : String(valor);
}

function formatDireccion(dir) {
    if (!dir) return '—';
    return `${dir.calle} ${dir.numero_exterior}${dir.numero_interior ? ` Int. ${dir.numero_interior}` : ''}, ${dir.colonia}, ${dir.municipio?.nombre ?? ''}, C.P. ${dir.codigo_postal}`;
}

function formatEdad(historia) {
    if (!historia.edad_unidad || historia.edad_unidad === 'NR') return 'No registrada';
    if (historia.edad_unidad === 'NA') return 'No aplica';
    return `${historia.edad_valor ?? '—'} ${historia.edad_unidad}`;
}

function Seccion({ titulo, children }) {
    return (
        <div>
            <h3 className="border-b border-gray-200 pb-2 text-base font-semibold text-gray-800">
                {titulo}
            </h3>
            <dl className="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                {children}
            </dl>
        </div>
    );
}

function Campo({ etiqueta, children }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-wider text-gray-500">{etiqueta}</dt>
            <dd className="mt-0.5 text-sm text-gray-900">{children ?? '—'}</dd>
        </div>
    );
}

export default function Show({ historia }) {
    const propietario = [historia.propietario?.nombre, historia.propietario?.apellidos].filter(Boolean).join(' ');
    const muestras = historia.muestras ?? [];
    const respuestas = historia.respuestas ?? [];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Historia clínica #{String(historia.numero_caso).padStart(4, '0')}
                    </h2>
                    <div className="flex items-center gap-3">
                        <EstadoBadge estado={historia.estado} />
                        <Link
                            href={route('historias-clinicas.edit', historia.id)}
                            className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                        >
                            Editar
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Historia clínica #${String(historia.numero_caso).padStart(4, '0')}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <Seccion titulo="Datos generales">
                            <Campo etiqueta="Ejercicio">{historia.ejercicio}</Campo>
                            <Campo etiqueta="No. caso">{String(historia.numero_caso).padStart(4, '0')}</Campo>
                            <Campo etiqueta="Fecha de recepción">{formatFecha(historia.fecha_recepcion)}</Campo>
                            <Campo etiqueta="Fecha de muestra">{formatFecha(historia.fecha_muestra)}</Campo>
                            <Campo etiqueta="Impreso">{IMPRESOS[historia.impreso] ?? '—'}</Campo>
                            <Campo etiqueta="Registró">{historia.creado_por?.name}</Campo>
                        </Seccion>
                    </div>

                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <Seccion titulo="Propietario">
                            <Campo etiqueta="Nombre">{propietario}</Campo>
                            <Campo etiqueta="Teléfono">{historia.propietario?.telefono}</Campo>
                            <Campo etiqueta="CURP">{historia.propietario?.curp}</Campo>
                            <Campo etiqueta="RFC">{historia.propietario?.rfc}</Campo>
                            <div className="sm:col-span-2">
                                <Campo etiqueta="Dirección">{formatDireccion(historia.direccion)}</Campo>
                            </div>
                            {historia.explotacion && (
                                <div className="sm:col-span-2">
                                    <Campo etiqueta="Explotación">
                                        {historia.explotacion.nombre}
                                        {historia.explotacion.municipio?.nombre ? ` — ${historia.explotacion.municipio.nombre}` : ''}
                                    </Campo>
                                </div>
                            )}
                        </Seccion>
                    </div>

                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <Seccion titulo="Datos del animal">
                            <Campo etiqueta="Especie">{historia.especie?.nombre}</Campo>
                            <Campo etiqueta="Raza">{historia.raza?.nombre}</Campo>
                            <Campo etiqueta="Función zootécnica">{historia.funcion_zootecnica?.nombre}</Campo>
                            <Campo etiqueta="Sexo">{historia.sexo}</Campo>
                            <Campo etiqueta="Edad">{formatEdad(historia)}</Campo>
                            <Campo etiqueta="Cantidad">{historia.cantidad}</Campo>
                            <Campo etiqueta="Animales en la explotación">{historia.animales_explotacion}</Campo>
                            <Campo etiqueta="Animales muertos">{historia.animales_muertos}</Campo>
                            <Campo etiqueta="Animales enfermos">{historia.animales_enfermos}</Campo>
                            <div className="sm:col-span-2">
                                <Campo etiqueta="Notas adicionales">{historia.notas_adicionales}</Campo>
                            </div>
                        </Seccion>
                    </div>

                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="border-b border-gray-200 pb-2 text-base font-semibold text-gray-800">
                            Servicios de análisis
                        </h3>
                        {muestras.length === 0 ? (
                            <p className="mt-3 text-sm text-gray-500">Sin muestras registradas.</p>
                        ) : (
                            <div className="mt-3 space-y-3">
                                {muestras.map((muestra, i) => (
                                    <div key={muestra.id ?? i} className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <dl className="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                                            <Campo etiqueta="Análisis">{muestra.prueba?.nombre}</Campo>
                                            <Campo etiqueta="Cantidad">{muestra.cantidad}</Campo>
                                            <Campo etiqueta="Tipo de muestra">{muestra.tipo_muestra?.nombre}</Campo>
                                            {muestra.notas && (
                                                <div className="sm:col-span-3">
                                                    <Campo etiqueta="Notas">{muestra.notas}</Campo>
                                                </div>
                                            )}
                                        </dl>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {respuestas.length > 0 && (
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="border-b border-gray-200 pb-2 text-base font-semibold text-gray-800">
                                Cuestionario
                            </h3>
                            <dl className="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                {respuestas.map((respuesta) => (
                                    <Campo key={respuesta.id} etiqueta={respuesta.pregunta?.texto}>
                                        {respuesta.respuesta}
                                    </Campo>
                                ))}
                            </dl>
                        </div>
                    )}

                    <Link
                        href={route('historias-clinicas.index')}
                        className="inline-block text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Volver a historias clínicas
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
