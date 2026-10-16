import DatePickerInput from '@/Components/DatePickerInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function fecha(valor) {
    return new Date(valor).toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

export default function Index({ notas, filtros, puede: permisos }) {
    const { flash } = usePage().props;

    const [form, setForm] = useState({
        buscar: filtros.buscar ?? '',
        fecha_desde: filtros.fecha_desde ?? '',
        fecha_hasta: filtros.fecha_hasta ?? '',
        pagada: filtros.pagada ?? '',
    });

    function setCampo(campo, valor) {
        setForm((prev) => ({ ...prev, [campo]: valor }));
    }

    function buscar(e) {
        e.preventDefault();
        router.get(route('notas-pago.index'), form, { preserveState: true, replace: true });
    }

    function limpiar() {
        const vacio = { buscar: '', fecha_desde: '', fecha_hasta: '', pagada: '' };
        setForm(vacio);
        router.get(route('notas-pago.index'), vacio, { preserveState: true, replace: true });
    }

    function goToPage(url) {
        if (!url) return;
        const page = new URL(url).searchParams.get('page');
        router.get(route('notas-pago.index'), { ...filtros, page }, { preserveState: true, replace: true });
    }

    const inputClass = 'block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand';
    const thClass = 'px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Notas de pago - Listado</h2>
                    {permisos.crear && (
                        <Link
                            href={route('notas-pago.create')}
                            className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                        >
                            Nueva nota de pago
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Notas de pago" />

            <div className="py-12">
                <div className="mx-auto max-w-6xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <form onSubmit={buscar} className="mb-4 rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Buscar</label>
                                <input
                                    type="text"
                                    value={form.buscar}
                                    onChange={(e) => setCampo('buscar', e.target.value)}
                                    placeholder="Folio, cliente, RFC o caso"
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Desde</label>
                                <DatePickerInput
                                    value={form.fecha_desde}
                                    onChange={(v) => setCampo('fecha_desde', v)}
                                    className="w-full px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Hasta</label>
                                <DatePickerInput
                                    value={form.fecha_hasta}
                                    onChange={(v) => setCampo('fecha_hasta', v)}
                                    className="w-full px-3 py-1.5 text-sm"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Pagada</label>
                                <select value={form.pagada} onChange={(e) => setCampo('pagada', e.target.value)} className={inputClass}>
                                    <option value="">Todas</option>
                                    <option value="1">Sí</option>
                                    <option value="0">No</option>
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
                                    <th className={thClass}>Folio</th>
                                    <th className={thClass}>Fecha</th>
                                    <th className={thClass}>Cliente</th>
                                    <th className={thClass}>Casos</th>
                                    <th className={thClass}>Concepto</th>
                                    <th className={thClass}>Tipo de pago</th>
                                    <th className={`${thClass} text-right`}>Total</th>
                                    <th className={thClass}>Pagada</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {notas.data.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron notas de pago con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {notas.data.map((nota) => (
                                    <tr key={nota.id}>
                                        <td className="px-6 py-4 font-mono text-sm font-medium text-gray-900">{nota.folio}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{fecha(nota.created_at)}</td>
                                        <td className="px-6 py-4 text-sm text-gray-900">
                                            {nota.cliente?.nombre ?? '—'}
                                            {nota.cliente?.rfc && <div className="font-mono text-xs text-gray-400">{nota.cliente.rfc}</div>}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            {nota.historias_clinicas.map((h) => h.numero_caso).join(', ') || '—'}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{nota.concepto}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{nota.tipo_pago}</td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
                                            {moneda.format(nota.total)}
                                        </td>
                                        <td className="px-6 py-4 text-sm">
                                            {nota.pagada ? (
                                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sí</span>
                                            ) : (
                                                <span className="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">No</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {notas.links.length > 3 && (
                            <div className="flex justify-center gap-1 border-t px-6 py-4">
                                {notas.links.map((link, i) => (
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
