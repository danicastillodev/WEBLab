import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ areas, usuarios }) {
    const { flash } = usePage().props;
    const [areaSeleccionada, setAreaSeleccionada] = useState(null);
    const [usuarioAAgregar, setUsuarioAAgregar] = useState('');

    function handleDelete(area) {
        if (confirm(`¿Eliminar el área "${area.nombre}"?`)) {
            router.delete(route('areas.destroy', area.id));
        }
    }

    function agregarUsuario(e) {
        e.preventDefault();

        if (!usuarioAAgregar) {
            return;
        }

        router.post(
            route('areas.usuarios.store', areaSeleccionada),
            { user_id: Number(usuarioAAgregar) },
            { preserveScroll: true, onSuccess: () => setUsuarioAAgregar('') },
        );
    }

    function quitarUsuario(usuario) {
        router.delete(
            route('areas.usuarios.destroy', [areaSeleccionada, usuario.id]),
            { preserveScroll: true },
        );
    }

    const areaActual = areas.find((a) => a.id === areaSeleccionada);
    const miembros = areaActual?.users ?? [];
    const areaNombre = areaActual?.nombre;

    // Sólo se ofrecen los usuarios que aún no pertenecen al área.
    const disponibles = usuarios.filter(
        (u) => !miembros.some((m) => m.id === u.id),
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Áreas</h2>
                    <Link
                        href={route('areas.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nueva área
                    </Link>
                </div>
            }
        >
            <Head title="Áreas" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Left: list of areas */}
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="border-b border-gray-200 px-6 py-3">
                                <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider">Áreas</h3>
                            </div>
                            {areas.length === 0 ? (
                                <p className="px-6 py-4 text-center text-sm text-gray-500">
                                    Aún no hay áreas.
                                </p>
                            ) : (
                                <ul className="divide-y divide-gray-200">
                                    {areas.map((area) => (
                                        <li
                                            key={area.id}
                                            onClick={() => setAreaSeleccionada(area.id)}
                                            className={`flex cursor-pointer items-center justify-between px-6 py-3 transition-colors ${
                                                areaSeleccionada === area.id
                                                    ? 'bg-brand text-white'
                                                    : 'hover:bg-gray-50'
                                            }`}
                                        >
                                            <div>
                                                <span className="text-sm font-medium">{area.nombre}</span>
                                                <span
                                                    className={`ml-2 text-xs ${areaSeleccionada === area.id ? 'text-indigo-200' : 'text-gray-400'}`}
                                                >
                                                    {area.users_count} {area.users_count === 1 ? 'usuario' : 'usuarios'}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-3 text-sm" onClick={(e) => e.stopPropagation()}>
                                                <Link
                                                    href={route('areas.edit', area.id)}
                                                    className={areaSeleccionada === area.id ? 'text-indigo-200 hover:text-white' : 'text-indigo-600 hover:text-indigo-900'}
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(area)}
                                                    className={areaSeleccionada === area.id ? 'text-red-300 hover:text-white' : 'text-red-600 hover:text-red-900'}
                                                >
                                                    Eliminar
                                                </button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        {/* Right: users for selected area */}
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            {areaSeleccionada === null ? (
                                <div className="flex h-full items-center justify-center px-6 py-12 text-sm text-gray-400">
                                    Selecciona un área para ver sus usuarios
                                </div>
                            ) : (
                                <>
                                    <div className="border-b border-gray-200 px-6 py-3">
                                        <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider">
                                            {areaNombre}
                                        </h3>
                                    </div>
                                    {miembros.length === 0 ? (
                                        <p className="px-6 py-4 text-center text-sm text-gray-500">
                                            Esta área no tiene usuarios asignados.
                                        </p>
                                    ) : (
                                        <ul className="divide-y divide-gray-200">
                                            {miembros.map((usuario) => (
                                                <li
                                                    key={usuario.id}
                                                    className="flex items-center justify-between px-6 py-3 text-sm text-gray-700"
                                                >
                                                    {usuario.name}
                                                    <button
                                                        onClick={() => quitarUsuario(usuario)}
                                                        className="text-red-600 hover:text-red-900"
                                                    >
                                                        Quitar
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}

                                    <form
                                        onSubmit={agregarUsuario}
                                        className="flex items-center gap-2 border-t border-gray-200 px-6 py-4"
                                    >
                                        <select
                                            value={usuarioAAgregar}
                                            onChange={(e) => setUsuarioAAgregar(e.target.value)}
                                            className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">— Selecciona un usuario —</option>
                                            {disponibles.map((usuario) => (
                                                <option key={usuario.id} value={usuario.id}>
                                                    {usuario.name}
                                                </option>
                                            ))}
                                        </select>
                                        <button
                                            type="submit"
                                            disabled={!usuarioAAgregar}
                                            className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover disabled:opacity-50"
                                        >
                                            Agregar
                                        </button>
                                    </form>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
