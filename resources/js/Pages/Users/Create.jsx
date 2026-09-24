import { matrizVacia, permisosGranularesVacios } from '@/Components/AccesoFields';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import UserForm from './_form';

export default function Create({ areas, modulos, acciones, dias, gruposPermisos }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        area_ids: [],
        password: '',
        password_confirmation: '',
        es_admin: false,
        hora_inicio: '',
        hora_fin: '',
        dias_permitidos: [],
        permisos: matrizVacia(modulos, acciones),
        permisos_granulares: permisosGranularesVacios(gruposPermisos),
    });

    function submit(e) {
        e.preventDefault();
        post(route('users.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Nuevo usuario</h2>}
        >
            <Head title="Nuevo usuario" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <UserForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            submit={submit}
                            etiquetaBoton="Crear"
                            areas={areas}
                            modulos={modulos}
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
