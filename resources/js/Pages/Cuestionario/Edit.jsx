import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PreguntaForm from './_form';

export default function Edit({ pregunta }) {
    const { data, setData, patch, processing, errors } = useForm({
        texto:  pregunta.texto,
        tipo:   pregunta.tipo,
        orden:  pregunta.orden,
        activa: pregunta.activa,
    });

    function submit(e) {
        e.preventDefault();
        patch(route('cuestionario.update', pregunta.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar pregunta</h2>}
        >
            <Head title="Editar pregunta" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <PreguntaForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            submit={submit}
                            etiquetaBoton="Guardar"
                            respuestasCount={pregunta.respuestas_count ?? 0}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
