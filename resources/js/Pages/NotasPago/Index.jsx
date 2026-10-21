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
    const [modalFolio, setModalFolio] = useState(flash?.nota_pago_folio ?? null);
    const notaPagoId = flash?.nota_pago_id ?? null;

    /* ── Cancelar (conserva la nota y su folio, con motivo) ── */
    const [notaACancelar, setNotaACancelar] = useState(null);
    const [motivo, setMotivo] = useState('');
    const [errorMotivo, setErrorMotivo] = useState('');
    const [cancelando, setCancelando] = useState(false);

    function abrirCancelar(nota) {
        setNotaACancelar(nota);
        setMotivo('');
        setErrorMotivo('');
    }

    function confirmarCancelar(e) {
        e.preventDefault();
        router.patch(route('notas-pago.cancelar', notaACancelar.id), { motivo_cancelacion: motivo }, {
            preserveScroll: true,
            onStart: () => setCancelando(true),
            onFinish: () => setCancelando(false),
            onSuccess: () => setNotaACancelar(null),
            onError: (errs) => setErrorMotivo(errs.motivo_cancelacion ?? 'No se pudo cancelar la nota de pago.'),
        });
    }

    function eliminar(nota) {
        if (!window.confirm(`¿Eliminar definitivamente la nota de pago folio ${nota.folio}? Esta acción no se puede deshacer.`)) return;
        router.delete(route('notas-pago.destroy', nota.id), { preserveScroll: true });
    }

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
                    {flash?.error && (
                        <div className="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                            {flash.error}
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
                                    <th className={thClass}><span className="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {notas.data.length === 0 && (
                                    <tr>
                                        <td colSpan={9} className="px-6 py-4 text-center text-sm text-gray-500">
                                            No se encontraron notas de pago con los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {notas.data.map((nota) => (
                                    <tr key={nota.id} className={nota.cancelada ? 'bg-gray-50 text-gray-400' : ''}>
                                        <td className="px-6 py-4 font-mono text-sm font-medium text-gray-900">
                                            {nota.folio}
                                            {nota.cancelada && (
                                                <div
                                                    className="mt-1 inline-block cursor-help rounded-full bg-red-100 px-2 py-0.5 font-sans text-xs font-medium text-red-700"
                                                    title={`Motivo: ${nota.motivo_cancelacion}`}
                                                >
                                                    Cancelada
                                                </div>
                                            )}
                                        </td>
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
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <div className="inline-flex items-center gap-3">
                                                {permisos.editar && !nota.cancelada && (
                                                    <Link
                                                        href={route('notas-pago.edit', nota.id)}
                                                        className="inline-flex items-center text-indigo-600 hover:text-indigo-900"
                                                        title="Editar nota de pago"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                        </svg>
                                                    </Link>
                                                )}
                                                {permisos.cancelar && !nota.cancelada && (
                                                    <button
                                                        type="button"
                                                        onClick={() => abrirCancelar(nota)}
                                                        className="inline-flex items-center text-amber-600 hover:text-amber-800"
                                                        title="Cancelar nota de pago"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                    </button>
                                                )}
                                                {permisos.eliminar && (
                                                    <button
                                                        type="button"
                                                        onClick={() => eliminar(nota)}
                                                        className="inline-flex items-center text-red-600 hover:text-red-800"
                                                        title="Eliminar nota de pago"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </button>
                                                )}
                                                <Link
                                                    href={route('notas-pago.imprimir', nota.id)}
                                                    className="inline-flex items-center text-gray-500 hover:text-gray-800"
                                                    title="Imprimir nota de pago"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.090 48.090 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                                                    </svg>
                                                </Link>
                                            </div>
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

            {notaACancelar && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <form onSubmit={confirmarCancelar} className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <h3 className="text-lg font-semibold text-gray-900">Cancelar nota de pago folio {notaACancelar.folio}</h3>
                        <p className="mt-2 text-sm text-gray-600">
                            La nota se conserva en el listado como cancelada, con su folio. Sus historias clínicas
                            quedarán disponibles para facturarse en otra nota de pago.
                        </p>
                        <label className="mt-4 block text-sm font-medium text-gray-700" htmlFor="motivo_cancelacion">
                            Motivo de la cancelación <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            id="motivo_cancelacion"
                            value={motivo}
                            onChange={(e) => setMotivo(e.target.value)}
                            rows={3}
                            maxLength={1000}
                            autoFocus
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        {errorMotivo && <p className="mt-1 text-sm text-red-600">{errorMotivo}</p>}
                        <div className="mt-4 flex justify-end gap-3">
                            <button
                                type="button"
                                onClick={() => setNotaACancelar(null)}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Volver
                            </button>
                            <button
                                type="submit"
                                disabled={cancelando}
                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                            >
                                Cancelar nota de pago
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {modalFolio && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="mx-4 w-full max-w-sm rounded-lg bg-white p-8 text-center shadow-xl">
                        <div className="mb-4 flex justify-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                                <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900">Nota de pago guardada</h3>
                        <p className="mt-3 text-sm text-gray-700">
                            La nota de pago con el folio: <span className="font-bold text-brand">{modalFolio}</span> se ha guardado correctamente.
                        </p>
                        <p className="mt-3 text-sm font-medium text-gray-700">¿Desea imprimirla ahora?</p>
                        <div className="mt-3 flex gap-3">
                            <button
                                type="button"
                                onClick={() => setModalFolio(null)}
                                className="flex-1 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                No
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    window.open(route('notas-pago.imprimir', notaPagoId), `nota-pago-${notaPagoId}`, 'width=900,height=1000');
                                    setModalFolio(null);
                                }}
                                className="flex-1 rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover"
                            >
                                Sí
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
