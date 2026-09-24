import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

// Las secciones de "Impresión de facturas" abajo abren un flujo que ningún
// card describe todavía (editor de posiciones de campos), así que su botón
// queda sin acción, igual que los placeholders "#" del grupo Facturación.
export default function Edit({ parametros }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        folio_automatico_activo: parametros.folio_automatico_activo ?? true,
        proximo_folio: parametros.proximo_folio ?? '',
        iva_porcentaje: parametros.iva_porcentaje ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('parametros-operacion.update'));
    }

    const fieldsetClass = 'space-y-4 border-t border-gray-100 pt-6 first:border-t-0 first:pt-0';
    const legendClass = 'text-sm font-semibold text-gray-800';

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Parámetros de Operación</h2>}
        >
            <Head title="Parámetros de Operación" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div className={fieldsetClass}>
                                <h3 className={legendClass}>Impresión de Facturas</h3>
                                <p className="text-sm text-gray-500">
                                    Para tener mayor control sobre la posición de cada uno de los campos que conforman la factura,
                                    es posible configurar dichos parámetros con el siguiente botón.
                                </p>
                                <div className="flex items-center gap-2">
                                    <SecondaryButton>Configurar factura...</SecondaryButton>
                                    <span className="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                        Prox
                                    </span>
                                </div>
                            </div>

                            <div className={fieldsetClass}>
                                <h3 className={legendClass}>Folio Automático en Facturas</h3>
                                <p className="text-sm text-gray-500">
                                    Puede seleccionar el comportamiento que más se adapte a lo descrito a continuación.
                                </p>
                                <div className="space-y-3">
                                    <label className="flex items-center gap-3 text-sm text-gray-700">
                                        <input
                                            type="radio"
                                            name="folio_automatico_activo"
                                            checked={data.folio_automatico_activo === true}
                                            onChange={() => setData('folio_automatico_activo', true)}
                                            className="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Activar la generación automática de folios
                                        {data.folio_automatico_activo && (
                                            <span className="ms-4 flex items-center gap-2">
                                                <span className="text-xs text-gray-500">Próximo folio:</span>
                                                <TextInput
                                                    type="number"
                                                    value={data.proximo_folio}
                                                    onChange={(e) => setData('proximo_folio', e.target.value)}
                                                    className="w-28"
                                                />
                                            </span>
                                        )}
                                    </label>
                                    <label className="flex items-center gap-3 text-sm text-gray-700">
                                        <input
                                            type="radio"
                                            name="folio_automatico_activo"
                                            checked={data.folio_automatico_activo === false}
                                            onChange={() => setData('folio_automatico_activo', false)}
                                            className="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Desactivar la generación automática de folios
                                    </label>
                                </div>
                                <InputError message={errors.proximo_folio} className="mt-2" />
                            </div>

                            <div className={fieldsetClass}>
                                <h3 className={legendClass}>IVA en Facturas</h3>
                                <p className="text-sm text-gray-500">
                                    Puede definir el porcentaje de IVA a aplicar a las facturas generadas en el sistema.
                                </p>
                                <div className="max-w-xs">
                                    <InputLabel htmlFor="iva_porcentaje" value="Porcentaje de IVA a aplicar" />
                                    <div className="mt-1 flex items-center gap-2">
                                        <TextInput
                                            id="iva_porcentaje"
                                            type="number"
                                            step="0.01"
                                            value={data.iva_porcentaje}
                                            onChange={(e) => setData('iva_porcentaje', e.target.value)}
                                            className="block w-full"
                                        />
                                        <span className="text-sm text-gray-500">%</span>
                                    </div>
                                    <InputError message={errors.iva_porcentaje} className="mt-2" />
                                </div>
                            </div>

                            <div className="flex items-center justify-between border-t border-gray-100 pt-6">
                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                                    <Link href={route('dashboard')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
