import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import UserForm from './_form';

export default function Edit({ user, areas, modulos, secciones, acciones, dias, gruposPermisos, roles }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        username: user.username ?? '',
        email: user.email,
        area_ids: user.area_ids ?? [],
        es_admin: user.es_admin,
        rol_id: user.rol_id ?? '',
        hora_inicio: user.hora_inicio ?? '',
        hora_fin: user.hora_fin ?? '',
        dias_permitidos: user.dias_permitidos ?? [],
        permisos: user.permisos,
        permisos_granulares: user.permisos_granulares,
    });

    const { flash } = usePage().props;

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
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}
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
                            secciones={secciones}
                            acciones={acciones}
                            dias={dias}
                            gruposPermisos={gruposPermisos}
                            roles={roles}
                            esEdicion
                            userId={user.id}
                            tieneContrasena={user.tiene_contrasena}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
