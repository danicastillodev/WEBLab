import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ users, filtros }) {
    const { flash } = usePage().props;

    const [buscarTexto, setBuscarTexto] = useState(filtros.buscar ?? '');

    function buscar(e) {
        e.preventDefault();
        router.get(route('users.index'), { buscar: buscarTexto }, { preserveState: true, replace: true });
    }

    function limpiar() {
        setBuscarTexto('');
        router.get(route('users.index'), {}, { preserveState: true, replace: true });
    }

    function handleDelete(user) {
        if (confirm(`¿Eliminar el usuario "${user.name}"?`)) {
            router.delete(route('users.destroy', user.id));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Usuarios
                    </h2>
                    <Link
                        href={route('users.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nuevo usuario
                    </Link>
                </div>
            }
        >
            <Head title="Usuarios" />

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
                                placeholder="Nombre, usuario, correo o área"
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
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Correo electrónico</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Área</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {users.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-4 text-center text-sm text-gray-500">
                                            {filtros.buscar
                                                ? 'Ningún usuario coincide con la búsqueda.'
                                                : 'Aún no hay usuarios.'}
                                        </td>
                                    </tr>
                                )}
                                {users.data.map((user) => (
                                    <tr key={user.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {user.name}
                                            {user.es_admin ? (
                                                <span className="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                    Administrador
                                                </span>
                                            ) : null}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{user.username}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{user.email}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {user.areas?.length
                                                ? user.areas.map((a) => a.nombre).join(', ')
                                                : '—'}
                                        </td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('users.edit', user.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(user)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {users.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {users.links.map((link, i) => (
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
