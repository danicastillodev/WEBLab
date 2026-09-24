import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

// Métodos sugeridos en el filtro; el campo del formulario admite cualquier
// valor libre, así que esta lista es sólo de referencia rápida.
export const METODOS = [
    'PCR',
    'Microscopía Directa',
    'Hidrólisis Alcalina',
    'Tinción de Wright',
    'Kirby Bauer',
    'Centrifugado',
    'Bacteriológico',
    'Número Más Probable',
    'Procedimiento Estándar',
];

export default function Index({ servicios, areas, filtros }) {
    const { flash } = usePage().props;

    const [form, setForm] = useState({
        buscar: filtros.buscar ?? '',
        area_id: filtros.area_id ?? '',
        metodo: filtros.metodo ?? '',
    });

    function handleChange(e) {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    }

    function buscar(e) {
        e.preventDefault();
        router.get(route('servicios.index'), form, { preserveState: true, replace: true });
    }

    function limpiar() {
        const vacio = { buscar: '', area_id: '', metodo: '' };
        setForm(vacio);
        router.get(route('servicios.index'), vacio, { preserveState: true, replace: true });
    }

    function goToPage(url) {
        if (!url) return;
        const page = new URL(url).searchParams.get('page');
        router.get(route('servicios.index'), { ...filtros, page }, { preserveState: true, replace: true });
    }

    function handleDelete(servicio) {
        if (confirm(`¿Eliminar el servicio "${servicio.clave}"?`)) {
            router.delete(route('servicios.destroy', servicio.id));
        }
    }

    const inputClass = 'block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Servicios - Listado</h2>
                    <Link
                        href={route('servicios.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nuevo servicio
                    </Link>
                </div>
            }
        >
            <Head title="Servicios" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <form onSubmit={buscar} className="mb-4 rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Buscar</label>
                                <input
                                    type="text"
                                    name="buscar"
                                    value={form.buscar}
                                    onChange={handleChange}
                                    placeholder="Clave, siglas o descripción"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Área</label>
                                <select name="area_id" value={form.area_id} onChange={handleChange} className={inputClass}>
                                    <option value="">Todas</option>
                                    {areas.map((a) => (
                                        <option key={a.id} value={a.id}>{a.nombre}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Método</label>
                                <select name="metodo" value={form.metodo} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    {METODOS.map((m) => (
                                        <option key={m} value={m}>{m}</option>
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
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Clave</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Siglas</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Descripción</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Método</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Área</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Referencias</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {servicios.data.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron servicios con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {servicios.data.map((servicio) => (
                                    <tr key={servicio.id}>
                                        <td className="px-6 py-4 font-mono text-sm font-medium text-gray-900">{servicio.clave}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{servicio.siglas ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{servicio.descripcion}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{servicio.metodo ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{servicio.area?.nombre ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{servicio.referencias ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm">
                                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${servicio.activo ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200'}`}>
                                                {servicio.activo ? 'Activo' : 'Inactivo'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('servicios.edit', servicio.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(servicio)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {servicios.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {servicios.links.map((link, i) => (
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
