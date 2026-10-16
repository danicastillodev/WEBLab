import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';
import ClienteForm, { CLIENTE_VACIO } from './Partials/ClienteForm';

export default function Edit({ cliente, estados, municipios }) {
    const { data, setData, patch, processing, errors } = useForm(
        Object.fromEntries(Object.keys(CLIENTE_VACIO).map((k) => [k, cliente[k] == null ? '' : String(cliente[k])])),
    );

    function submit(e) {
        e.preventDefault();
        patch(route('clientes.update', cliente.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar cliente</h2>}
        >
            <Head title="Editar cliente" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <ClienteForm data={data} setData={setData} errors={errors} estados={estados} municipios={municipios} />
                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Guardar cambios</PrimaryButton>
                                <Link href={route('clientes.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
