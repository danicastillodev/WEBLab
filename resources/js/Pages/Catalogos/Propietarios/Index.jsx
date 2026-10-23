import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ propietarios, filtros }) {
    const { flash } = usePage().props;

    const [buscarTexto, setBuscarTexto] = useState(filtros.buscar ?? '');

    function buscar(e) {
        e.preventDefault();
        router.get(route('propietarios.index'), { buscar: buscarTexto }, { preserveState: true, replace: true });
    }

    function limpiar() {
        setBuscarTexto('');
        router.get(route('propietarios.index'), {}, { preserveState: true, replace: true });
    }

    function handleDelete(propietario) {
        if (confirm(`¿Eliminar a "${propietario.nombre} ${propietario.apellidos}"?`)) {
            router.delete(route('propietarios.destroy', propietario.id));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Propietarios</h2>
                    <Link
                        href={route('propietarios.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nuevo propietario
                    </Link>
                </div>
            }
        >
            <Head title="Propietarios" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <form onSubmit={buscar} className="mb-4 rounded-lg bg-white p-4 shadow-sm">
                        <label className="mb-1 block text-xs font-medium text-gray-600">Buscar</label>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <input
                                type="text"
                                value={buscarTexto}
                                onChange={(e) => setBuscarTexto(e.target.value)}
                                placeholder="Nombre, apellidos, CURP, RFC o teléfono"
                                className="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                            />
                            <div className="flex gap-2">
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
                        </div>
                    </form>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Teléfono</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">CURP</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">RFC</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Direcciones</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Explotaciones</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {propietarios.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-4 text-center text-sm text-gray-500">
                                            {filtros.buscar
                                                ? 'Ningún propietario coincide con la búsqueda.'
                                                : 'Aún no hay propietarios registrados.'}
                                        </td>
                                    </tr>
                                )}
                                {propietarios.data.map((propietario) => (
                                    <tr key={propietario.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {propietario.apellidos}, {propietario.nombre}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{propietario.telefono ?? '—'}</td>
                                        <td className="px-6 py-4 font-mono text-sm text-gray-500">{propietario.curp ?? '—'}</td>
                                        <td className="px-6 py-4 font-mono text-sm text-gray-500">{propietario.rfc ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{propietario.direcciones_count}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{propietario.explotaciones_count}</td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('propietarios.edit', propietario.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(propietario)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {propietarios.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {propietarios.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-brand text-white' : 'text-gray-600 hover:bg-gray-100'} ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
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
