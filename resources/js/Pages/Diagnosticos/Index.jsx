import ComboboxSelect from '@/Components/ComboboxSelect';
import DatePickerInput from '@/Components/DatePickerInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { formatFechaHora } from '@/utils/fechas';
import { ESTADOS_ANALISIS, EstadoAnalisis } from './_estados';

const soloFecha = (valor) => formatFechaHora(valor?.slice(0, 10));

// Listado de los análisis de las historias clínicas de las áreas del usuario.
// Sin filtros llega con "Pendiente" seleccionado. Al pulsar una fila se abre
// la captura del diagnóstico de ese análisis.
export default function Index({ analisis, filtros, tieneAreas, servicios }) {
    const { flash } = usePage().props;

    const [form, setForm] = useState({
        estado:      filtros.estado      ?? '',
        numero_caso: filtros.numero_caso ?? '',
        fecha_desde: filtros.fecha_desde ?? '',
        fecha_hasta: filtros.fecha_hasta ?? '',
        servicio_id: filtros.servicio_id ?? '',
    });

    const opcionesServicio = servicios.map((s) => ({
        value: s.id,
        label: s.siglas ? `${s.siglas} — ${s.descripcion}` : s.descripcion,
    }));

    function handleChange(e) {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    }

    function buscar(e) {
        e.preventDefault();
        router.get(route('diagnosticos.index'), form, { preserveState: true, replace: true });
    }

    // Se manda el estado vacío (no omitido): sin el parámetro el controlador
    // vuelve a filtrar por pendientes.
    function limpiar() {
        const vacio = { estado: '', numero_caso: '', servicio_id: '', fecha_desde: '', fecha_hasta: '' };
        setForm(vacio);
        router.get(route('diagnosticos.index'), vacio, { preserveState: true, replace: true });
    }

    function goToPage(url) {
        if (!url) return;
        const page = new URL(url).searchParams.get('page');
        router.get(route('diagnosticos.index'), { ...filtros, page }, { preserveState: true, replace: true });
    }

    const inputClass = 'block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';
    const th = 'px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500';

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Listado de diagnósticos</h2>}
        >
            <Head title="Listado de diagnósticos" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}
                    {!tieneAreas && (
                        <div className="mb-4 rounded-md bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            No tienes áreas asignadas, así que no hay análisis que mostrar. Pide que te asignen un área en el Catálogo de Áreas.
                        </div>
                    )}

                    {/* Filtros */}
                    <form onSubmit={buscar} className="mb-4 rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Status</label>
                                <select name="estado" value={form.estado} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    {Object.entries(ESTADOS_ANALISIS).map(([valor, { etiqueta }]) => (
                                        <option key={valor} value={valor}>{etiqueta}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Caso</label>
                                <input
                                    type="number"
                                    name="numero_caso"
                                    value={form.numero_caso}
                                    onChange={handleChange}
                                    placeholder="Ej. 42"
                                    min="1"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Recepción desde</label>
                                <DatePickerInput
                                    value={form.fecha_desde}
                                    onChange={(v) => setForm((prev) => ({ ...prev, fecha_desde: v }))}
                                    className="w-full px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Recepción hasta</label>
                                <DatePickerInput
                                    value={form.fecha_hasta}
                                    onChange={(v) => setForm((prev) => ({ ...prev, fecha_hasta: v }))}
                                    className="w-full px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="mb-1 block text-xs font-medium text-gray-600">Análisis</label>
                                <ComboboxSelect
                                    value={form.servicio_id}
                                    onChange={(valor) => setForm((prev) => ({ ...prev, servicio_id: valor }))}
                                    options={opcionesServicio}
                                    placeholder="Todos"
                                />
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <button
                                type="submit"
                                className="rounded-md bg-brand px-4 py-1.5 text-sm font-medium text-white hover:bg-brand-hover"
                            >
                                Buscar
                            </button>
                            <button
                                type="button"
                                onClick={limpiar}
                                className="rounded-md border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50"
                            >
                                Limpiar
                            </button>
                        </div>
                    </form>

                    <div className="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className={th}>Caso</th>
                                    <th className={th}>Tipo</th>
                                    <th className={th}>Fecha Recepción</th>
                                    <th className={th}>Fecha Realización</th>
                                    <th className={th}>Fecha Emisión</th>
                                    <th className={th}>Muestras</th>
                                    <th className={th}>Análisis</th>
                                    <th className={th}>Status</th>
                                    <th className={th}>Usuario</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {analisis.data.length === 0 && (
                                    <tr>
                                        <td colSpan={9} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron análisis con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {analisis.data.map((m) => {
                                    const historia = m.historia_clinica;
                                    const diagnostico = m.diagnostico;
                                    return (
                                        <tr
                                            key={m.id}
                                            onClick={() => router.get(route('diagnosticos.edit', m.id))}
                                            className="cursor-pointer hover:bg-gray-50"
                                        >
                                            <td className="px-6 py-4 text-sm text-gray-900">{historia?.numero_caso ?? '—'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{m.servicio?.siglas ?? '—'}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{soloFecha(historia?.fecha_recepcion)}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{soloFecha(diagnostico?.fecha_realizacion)}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{soloFecha(diagnostico?.fecha_emision)}</td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{m.cantidad}</td>
                                            <td className="max-w-xs truncate px-6 py-4 text-sm text-gray-900" title={m.servicio?.descripcion}>
                                                {m.servicio?.descripcion ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 text-sm"><EstadoAnalisis estado={diagnostico?.estado} /></td>
                                            <td className="px-6 py-4 text-sm text-gray-500">{diagnostico?.creado_por?.name ?? ''}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {analisis.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {analisis.links.map((link, i) => (
                                    <button
                                        key={i}
                                        onClick={() => goToPage(link.url)}
                                        disabled={!link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-brand text-white' : 'text-gray-600 hover:bg-gray-100'} ${!link.url ? 'cursor-default opacity-40' : ''}`}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
