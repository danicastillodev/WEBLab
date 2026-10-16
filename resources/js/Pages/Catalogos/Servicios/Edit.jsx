import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import ServicioForm from './Partials/ServicioForm';

const comoTexto = (id) => (id ? String(id) : '');

export default function Edit({ servicio, ...catalogos }) {
    const { data, setData, patch, processing, errors } = useForm({
        clave: servicio.clave,
        siglas: servicio.siglas ?? '',
        descripcion: servicio.descripcion,
        nombre_analisis: servicio.nombre_analisis ?? '',
        metodo_id: comoTexto(servicio.metodo_id),
        categoria_id: comoTexto(servicio.categoria_id),
        tipos_muestra: servicio.tipos_muestra.map((t) => t.id),
        area_id: comoTexto(servicio.area_id),
        analista_id: comoTexto(servicio.analista_id),
        referencias: servicio.referencias ?? '',
        normas: servicio.normas.map((n) => n.id),
        compuesto: servicio.compuesto,
        independiente: servicio.independiente,
        activo: servicio.activo,
    });

    function submit(e) {
        e.preventDefault();
        patch(route('servicios.update', servicio.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar análisis</h2>}
        >
            <Head title="Editar análisis" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <ServicioForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            numero={servicio.numero}
                            textoGuardar="Guardar cambios"
                            catalogos={catalogos}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
