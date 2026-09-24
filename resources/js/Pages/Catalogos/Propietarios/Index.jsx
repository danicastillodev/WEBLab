import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ propietarios }) {
    const { flash } = usePage().props;

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

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Teléfono</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">CURP</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">RFC</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Direcciones</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {propietarios.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-4 text-center text-sm text-gray-500">
                                            Aún no hay propietarios registrados.
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
