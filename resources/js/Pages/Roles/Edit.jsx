import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import RolForm from './_form';

export default function Edit({ rol, modulos, secciones, acciones, dias, gruposPermisos }) {
    const { data, setData, patch, processing, errors } = useForm({
        nombre: rol.nombre,
        descripcion: rol.descripcion,
        hora_inicio: rol.hora_inicio,
        hora_fin: rol.hora_fin,
        dias_permitidos: rol.dias_permitidos,
        permisos: rol.permisos,
        permisos_granulares: rol.permisos_granulares,
    });

    function submit(e) {
        e.preventDefault();
        patch(route('roles.update', rol.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar rol</h2>}
        >
            <Head title="Editar rol" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <RolForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            submit={submit}
                            etiquetaBoton="Guardar"
                            modulos={modulos}
                            secciones={secciones}
                            acciones={acciones}
                            dias={dias}
                            gruposPermisos={gruposPermisos}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
