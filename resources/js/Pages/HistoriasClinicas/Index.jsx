import EstadoBadge, { ESTADOS } from '@/Components/EstadoBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

// Etiquetas de "impreso"; el valor guardado es el de la columna del mismo nombre.
const IMPRESOS = {
    si: 'Sí',
    no: 'No',
    parcial: 'Parcial',
};

const ejercicioActual = String(new Date().getFullYear());

export default function Index({ historias, filtros, especies }) {
    const { flash } = usePage().props;
    const [modalNumeroCaso, setModalNumeroCaso] = useState(flash?.numero_caso ?? null);
    const historiaId = flash?.historia_id ?? null;

    const [form, setForm] = useState({
        fecha_desde: filtros.fecha_desde ?? '',
        fecha_hasta: filtros.fecha_hasta ?? '',
        propietario: filtros.propietario ?? '',
        numero_caso: filtros.numero_caso ?? '',
        especie_id:  filtros.especie_id  ?? '',
        ejercicio:   filtros.ejercicio   ?? '',
        estado:      filtros.estado      ?? '',
        impreso:     filtros.impreso     ?? '',
    });

    function handleChange(e) {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    }

    function buscar(e) {
        e.preventDefault();
        router.get(route('historias-clinicas.index'), form, { preserveState: true, replace: true });
    }

    // Sólo vacía los campos: no lanza búsqueda, así que el listado sigue
    // mostrando el resultado anterior hasta que se pulse "Buscar".
    function limpiar() {
        setForm({
            fecha_desde: '', fecha_hasta: '', propietario: '', numero_caso: '',
            especie_id: '', ejercicio: '', estado: '', impreso: '',
        });
    }

    function goToPage(url) {
        if (!url) return;
        const page = new URL(url).searchParams.get('page');
        // Se pagina con los filtros ya aplicados, no con lo que haya escrito en
        // el formulario, que puede estar editado o recién vaciado sin buscar.
        router.get(route('historias-clinicas.index'), { ...filtros, page }, { preserveState: true, replace: true });
    }

    function filtrarHoy() {
        const hoy = new Date().toISOString().slice(0, 10);
        const hoyFiltro = {
            fecha_desde: hoy, fecha_hasta: hoy, propietario: '', numero_caso: '',
            especie_id: '', ejercicio: ejercicioActual, estado: '', impreso: 'no',
        };
        setForm(hoyFiltro);
        router.get(route('historias-clinicas.index'), hoyFiltro, { preserveState: true, replace: true });
    }

    function handleCancel(historia) {
        if (confirm('¿Cancelar esta historia clínica?')) {
            router.patch(route('historias-clinicas.cancelar', historia.id));
        }
    }

    const inputClass = 'block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Historias Clínicas</h2>
                    <Link
                        href={route('historias-clinicas.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nueva historia clínica
                    </Link>
                </div>
            }
        >
            <Head title="Historias Clínicas" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    {/* Filtros */}
                    <form onSubmit={buscar} className="mb-4 rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Fecha desde</label>
                                <input
                                    type="date"
                                    name="fecha_desde"
                                    value={form.fecha_desde}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Fecha hasta</label>
                                <input
                                    type="date"
                                    name="fecha_hasta"
                                    value={form.fecha_hasta}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Propietario</label>
                                <input
                                    type="text"
                                    name="propietario"
                                    value={form.propietario}
                                    onChange={handleChange}
                                    placeholder="Nombre o apellidos"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Núm. caso</label>
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
                                <label className="mb-1 block text-xs font-medium text-gray-600">Especie</label>
                                <select name="especie_id" value={form.especie_id} onChange={handleChange} className={inputClass}>
                                    <option value="">Todas</option>
                                    {especies.map((e) => (
                                        <option key={e.id} value={e.id}>{e.nombre}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Ejercicio</label>
                                <input
                                    type="number"
                                    name="ejercicio"
                                    value={form.ejercicio}
                                    onChange={handleChange}
                                    placeholder={ejercicioActual}
                                    min="2000"
                                    max="2100"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Status</label>
                                <select name="estado" value={form.estado} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    {Object.entries(ESTADOS).map(([valor, { etiqueta }]) => (
                                        <option key={valor} value={valor}>{etiqueta}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Impreso</label>
                                <select name="impreso" value={form.impreso} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    {Object.entries(IMPRESOS).map(([valor, etiqueta]) => (
                                        <option key={valor} value={valor}>{etiqueta}</option>
                                    ))}
                                </select>
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
                                onClick={filtrarHoy}
                                className="rounded-md border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50"
                            >
                                Hoy
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

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Ejercicio</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">No. Caso</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Propietario</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha Recepción</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Impreso</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Registró</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {historias.data.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron historias clínicas con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {historias.data.map((historia) => (
                                    <tr key={historia.id}>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.ejercicio ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {historia.numero_caso ? String(historia.numero_caso).padStart(4, '0') : '—'}
                                        </td>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {historia.propietario?.nombre} {historia.propietario?.apellidos}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.fecha_recepcion}</td>
                                        <td className="px-6 py-4 text-sm"><EstadoBadge estado={historia.estado} /></td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{IMPRESOS[historia.impreso] ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.creado_por?.name ?? '—'}</td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <div className="flex items-center justify-end gap-4">
                                                <Link
                                                    href={route('historias-clinicas.ticket', historia.id)}
                                                    className="inline-flex items-center text-gray-500 hover:text-gray-800"
                                                    title="Imprimir ticket"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.090 48.090 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                                    </svg>
                                                </Link>
                                                <Link
                                                    href={route('historias-clinicas.show', historia.id)}
                                                    className="inline-flex items-center text-indigo-600 hover:text-indigo-900"
                                                    title="Consultar"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                </Link>
                                                {historia.estado !== 'cancelada' && (
                                                    <button
                                                        onClick={() => handleCancel(historia)}
                                                        className="inline-flex items-center text-red-600 hover:text-red-900"
                                                        title="Cancelar"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {historias.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {historias.links.map((link, i) => (
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

            {modalNumeroCaso && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="mx-4 w-full max-w-sm rounded-lg bg-white p-8 shadow-xl text-center">
                        <div className="mb-4 flex justify-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                                <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900">Historia clínica registrada</h3>
                        <p className="mt-2 text-sm text-gray-500">Número de caso:</p>
                        <p className="mt-3 text-4xl font-bold text-brand">{String(modalNumeroCaso).padStart(4, '0')}</p>
                        <p className="mt-6 text-sm font-medium text-gray-700">¿Desea imprimir ticket?</p>
                        <div className="mt-3 flex gap-3">
                            <button
                                onClick={() => setModalNumeroCaso(null)}
                                className="flex-1 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                No
                            </button>
                            <Link
                                href={route('historias-clinicas.ticket', historiaId)}
                                className="flex-1 rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover"
                            >
                                Sí, imprimir
                            </Link>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
