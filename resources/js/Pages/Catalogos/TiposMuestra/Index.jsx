import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ tiposMuestra }) {
    const { flash } = usePage().props;

    function handleDelete(tipo) {
        if (confirm(`¿Eliminar el tipo de muestra "${tipo.nombre}"?`)) {
            router.delete(route('tipos-muestra.destroy', tipo.id));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Tipos de Muestra</h2>
                    <Link
                        href={route('tipos-muestra.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nuevo tipo de muestra
                    </Link>
                </div>
            }
        >
            <Head title="Tipos de Muestra" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
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
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {tiposMuestra.data.length === 0 && (
                                    <tr>
                                        <td colSpan={2} className="px-6 py-4 text-center text-sm text-gray-500">
                                            Aún no hay tipos de muestra registrados.
                                        </td>
                                    </tr>
                                )}
                                {tiposMuestra.data.map((tipo) => (
                                    <tr key={tipo.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">{tipo.nombre}</td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('tipos-muestra.edit', tipo.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(tipo)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {tiposMuestra.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {tiposMuestra.links.map((link, i) => (
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
