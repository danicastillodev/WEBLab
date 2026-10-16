import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Index({ funciones, buscar }) {
    const { flash } = usePage().props;
    const [texto, setTexto] = useState(buscar ?? '');

    // Filtra en el servidor un momento después de dejar de teclear.
    useEffect(() => {
        if (texto === (buscar ?? '')) return;
        const t = setTimeout(() => {
            router.get(route('funcion-zootecnicas.index'), texto ? { buscar: texto } : {}, { preserveState: true, replace: true });
        }, 300);
        return () => clearTimeout(t);
    }, [texto]);

    function handleDelete(funcion) {
        if (confirm(`¿Eliminar "${funcion.nombre}"?`)) {
            router.delete(route('funcion-zootecnicas.destroy', funcion.id));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Funciones Zootécnicas</h2>
                    <Link
                        href={route('funcion-zootecnicas.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nueva función
                    </Link>
                </div>
            }
        >
            <Head title="Funciones Zootécnicas" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <input
                        type="search"
                        value={texto}
                        onChange={(e) => setTexto(e.target.value)}
                        placeholder="Buscar función…"
                        className="mb-4 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nombre</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Especies</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {funciones.data.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-4 text-center text-sm text-gray-500">
                                            {buscar ? 'Ninguna función coincide con la búsqueda.' : 'Aún no hay funciones zootécnicas registradas.'}
                                        </td>
                                    </tr>
                                )}
                                {funciones.data.map((funcion) => (
                                    <tr key={funcion.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">{funcion.nombre}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {funcion.especies.length === 0 ? '—' : (
                                                <div className="flex flex-wrap gap-1">
                                                    {funcion.especies.map((e) => (
                                                        <span key={e.id} className="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700">{e.nombre}</span>
                                                    ))}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('funcion-zootecnicas.edit', funcion.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(funcion)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {funciones.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {funciones.links.map((link, i) => (
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
