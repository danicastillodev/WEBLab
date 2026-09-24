import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PreguntaForm from './_form';

export default function Create({ siguienteOrden }) {
    const { data, setData, post, processing, errors } = useForm({
        texto:  '',
        tipo:   'texto',
        orden:  siguienteOrden,
        activa: true,
    });

    function submit(e) {
        e.preventDefault();
        post(route('cuestionario.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Nueva pregunta</h2>}
        >
            <Head title="Nueva pregunta" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <PreguntaForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            submit={submit}
                            etiquetaBoton="Crear"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
