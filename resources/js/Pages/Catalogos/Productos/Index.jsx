import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { etiquetaUnidad } from './unidadesPrecio';

export default function Index({ productos, servicios, filtros }) {
    const { flash } = usePage().props;

    const [form, setForm] = useState({
        buscar: filtros.buscar ?? '',
        servicio_id: filtros.servicio_id ?? '',
        activo: filtros.activo ?? '',
    });

    function handleChange(e) {
        setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
    }

    function buscar(e) {
        e.preventDefault();
        router.get(route('productos.index'), form, { preserveState: true, replace: true });
    }

    function limpiar() {
        const vacio = { buscar: '', servicio_id: '', activo: '' };
        setForm(vacio);
        router.get(route('productos.index'), vacio, { preserveState: true, replace: true });
    }

    function goToPage(url) {
        if (!url) return;
        const page = new URL(url).searchParams.get('page');
        router.get(route('productos.index'), { ...filtros, page }, { preserveState: true, replace: true });
    }

    function handleToggleActivo(producto) {
        const accion = producto.activo ? 'desactivar' : 'activar';
        if (confirm(`¿Seguro que deseas ${accion} el producto "${producto.nombre}"?`)) {
            router.patch(route('productos.update', producto.id), {
                nombre: producto.nombre,
                servicio_id: producto.servicio_id,
                precio_unitario: producto.precio_unitario,
                unidad_precio: producto.unidad_precio,
                activo: !producto.activo,
            });
        }
    }

    const inputClass = 'block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Productos - Listado</h2>
                    <Link
                        href={route('productos.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nuevo producto
                    </Link>
                </div>
            }
        >
            <Head title="Productos" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
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
                                    placeholder="Nombre del producto"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Análisis relacionado</label>
                                <select name="servicio_id" value={form.servicio_id} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    {servicios.map((s) => (
                                        <option key={s.id} value={s.id}>{s.descripcion}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Status</label>
                                <select name="activo" value={form.activo} onChange={handleChange} className={inputClass}>
                                    <option value="">Todos</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
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
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Análisis relacionado</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Precio Unit.</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {productos.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron productos con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {productos.data.map((producto) => (
                                    <tr key={producto.id}>
                                        <td className="px-6 py-4 text-sm font-medium text-gray-900">{producto.nombre}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{producto.servicio?.descripcion ?? '—'}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">${Number(producto.precio_unitario).toFixed(2)} {etiquetaUnidad(producto.unidad_precio)}</td>
                                        <td className="px-6 py-4 text-sm">
                                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${producto.activo ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-200'}`}>
                                                {producto.activo ? 'Activo' : 'Inactivo'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('productos.edit', producto.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleToggleActivo(producto)}
                                                className={producto.activo ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'}
                                            >
                                                {producto.activo ? 'Desactivar' : 'Activar'}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {productos.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {productos.links.map((link, i) => (
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
