import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function domicilio(cliente) {
    const interior = cliente.numero_interior ? ` int. ${cliente.numero_interior}` : '';
    return `${cliente.calle} ${cliente.numero_exterior}${interior}, ${cliente.colonia}, C.P. ${cliente.codigo_postal}`;
}

export default function Index({ clientes, estados, filtros, puede: permisos }) {
    const { flash } = usePage().props;

    const [form, setForm] = useState({
        buscar: filtros.buscar ?? '',
        estado_id: filtros.estado_id ?? '',
    });

    function handleChange(e) {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    }

    function buscar(e) {
        e.preventDefault();
        router.get(route('clientes.index'), form, { preserveState: true, replace: true });
    }

    function limpiar() {
        const vacio = { buscar: '', estado_id: '' };
        setForm(vacio);
        router.get(route('clientes.index'), vacio, { preserveState: true, replace: true });
    }

    function goToPage(url) {
        if (!url) return;
        const page = new URL(url).searchParams.get('page');
        router.get(route('clientes.index'), { ...filtros, page }, { preserveState: true, replace: true });
    }

    function handleDelete(cliente) {
        if (confirm(`¿Seguro que deseas eliminar al cliente "${cliente.nombre}"?`)) {
            router.delete(route('clientes.destroy', cliente.id), { preserveScroll: true });
        }
    }

    const inputClass = 'block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Clientes - Listado</h2>
                    {permisos.crear && (
                        <Link
                            href={route('clientes.create')}
                            className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                        >
                            Nuevo cliente
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Clientes" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                            {flash.error}
                        </div>
                    )}

                    <form onSubmit={buscar} className="mb-4 rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Buscar</label>
                                <input
                                    type="text"
                                    name="buscar"
                                    value={form.buscar}
                                    onChange={handleChange}
                                    placeholder="Nombre o RFC"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                                <select name="estado_id" value={form.estado_id} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    {estados.map((e) => (
                                        <option key={e.id} value={e.id}>{e.nombre}</option>
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
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">RFC</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Teléfono</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dirección</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Municipio / Estado</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Notas</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {clientes.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron clientes con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {clientes.data.map((cliente) => (
                                    <tr key={cliente.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">{cliente.nombre}</td>
                                        <td className="px-6 py-4 font-mono text-sm text-gray-500">{cliente.rfc}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{cliente.telefono}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{domicilio(cliente)}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {cliente.municipio?.nombre}, {cliente.estado?.nombre}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{cliente.notas_pago_count}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            {permisos.editar && (
                                                <Link
                                                    href={route('clientes.edit', cliente.id)}
                                                    className="mr-4 text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Editar
                                                </Link>
                                            )}
                                            {permisos.eliminar && (
                                                <button
                                                    onClick={() => handleDelete(cliente)}
                                                    disabled={cliente.notas_pago_count > 0}
                                                    title={cliente.notas_pago_count > 0 ? 'Tiene notas de pago, no se puede eliminar' : undefined}
                                                    className="text-red-600 hover:text-red-900 disabled:cursor-not-allowed disabled:opacity-40"
                                                >
                                                    Eliminar
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {clientes.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {clientes.links.map((link, i) => (
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
