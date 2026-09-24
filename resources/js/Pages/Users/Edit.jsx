import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import UserForm from './_form';

export default function Edit({ user, areas, modulos, acciones, dias, gruposPermisos }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        username: user.username ?? '',
        email: user.email,
        area_ids: user.area_ids ?? [],
        password: '',
        password_confirmation: '',
        es_admin: user.es_admin,
        hora_inicio: user.hora_inicio ?? '',
        hora_fin: user.hora_fin ?? '',
        dias_permitidos: user.dias_permitidos ?? [],
        permisos: user.permisos,
        permisos_granulares: user.permisos_granulares,
    });

    function submit(e) {
        e.preventDefault();
        patch(route('users.update', user.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar usuario</h2>}
        >
            <Head title="Editar usuario" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <UserForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            submit={submit}
                            etiquetaBoton="Guardar"
                            areas={areas}
                            modulos={modulos}
                            acciones={acciones}
                            dias={dias}
                            gruposPermisos={gruposPermisos}
                            esEdicion
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
