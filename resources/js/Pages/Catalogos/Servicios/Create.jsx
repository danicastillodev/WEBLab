import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import ServicioForm from './Partials/ServicioForm';

export default function Create({ siguienteNumero, ...catalogos }) {
    const { data, setData, post, processing, errors } = useForm({
        clave: '',
        siglas: '',
        descripcion: '',
        nombre_analisis: '',
        metodo_id: '',
        categoria_id: '',
        tipos_muestra: [],
        area_id: '',
        analista_id: '',
        referencias: '',
        normas: [],
        compuesto: false,
        independiente: false,
        activo: true,
    });

    function submit(e) {
        e.preventDefault();
        post(route('servicios.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Análisis - Nuevo</h2>}
        >
            <Head title="Nuevo análisis" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <ServicioForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            numero={siguienteNumero}
                            textoGuardar="Guardar Análisis"
                            catalogos={catalogos}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
