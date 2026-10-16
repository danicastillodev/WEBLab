import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FormErrorAlert from '@/Components/FormErrorAlert';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';
import ClienteForm, { clienteNuevo } from './Partials/ClienteForm';

export default function Create({ estados, municipios }) {
    const { data, setData, post, processing, errors } = useForm(clienteNuevo(estados));

    function submit(e) {
        e.preventDefault();
        post(route('clientes.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Registrar Nuevo Cliente</h2>}
        >
            <Head title="Nuevo cliente" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <FormErrorAlert errors={errors} />
                            <ClienteForm data={data} setData={setData} errors={errors} estados={estados} municipios={municipios} />
                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Guardar Cliente</PrimaryButton>
                                <Link href={route('clientes.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
