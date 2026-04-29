import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ historias }) {
    const { flash } = usePage().props;

    function handleDelete(historia) {
        if (confirm('¿Eliminar esta historia clínica?')) {
            router.delete(route('historias-clinicas.destroy', historia.id));
        }
    }

    function formatEdad(historia) {
        if (historia.edad_unidad === 'NR') return 'NR';
        if (historia.edad_unidad === 'NA') return 'NA';
        return `${historia.edad_valor} ${historia.edad_unidad}`;
    }

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

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Propietario</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha Recepción</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Especie</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Raza</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Edad</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cantidad</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {historias.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-4 text-center text-sm text-gray-500">
                                            Aún no hay historias clínicas registradas.
                                        </td>
                                    </tr>
                                )}
                                {historias.data.map((historia) => (
                                    <tr key={historia.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">
                                            {historia.propietario?.apellidos}, {historia.propietario?.nombre}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.fecha_recepcion}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.especie?.nombre}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.raza?.nombre}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{formatEdad(historia)}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{historia.cantidad}</td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('historias-clinicas.edit', historia.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(historia)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {historias.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {historias.links.map((link, i) => (
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
