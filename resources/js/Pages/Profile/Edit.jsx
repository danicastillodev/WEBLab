import Avatar from '@/Components/Avatar';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PhotoIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const OPCIONES_AVATAR = [
    { tipo: 'inicial', etiqueta: 'Primera letra' },
    { tipo: 'iniciales', etiqueta: 'Iniciales' },
    { tipo: 'foto', etiqueta: 'Foto' },
];

export default function Edit({ esAdmin, rol, secciones }) {
    const { auth, flash } = usePage().props;
    const user = auth.user;

    const { data, setData, post, processing, errors, isDirty, reset } = useForm({
        avatar_tipo: user.avatar_tipo ?? 'inicial',
        foto: null,
    });

    const inputFoto = useRef(null);
    const [vistaPrevia, setVistaPrevia] = useState(null);

    useEffect(() => {
        if (!data.foto) {
            setVistaPrevia(null);
            return;
        }
        const url = URL.createObjectURL(data.foto);
        setVistaPrevia(url);
        return () => URL.revokeObjectURL(url);
    }, [data.foto]);

    const fotoUrl = vistaPrevia ?? user.avatar_url;

    function elegir(tipo) {
        setData('avatar_tipo', tipo);
        if (tipo === 'foto' && !fotoUrl) inputFoto.current?.click();
    }

    function seleccionarFoto(e) {
        const archivo = e.target.files?.[0];
        if (!archivo) return;
        setData((prev) => ({ ...prev, foto: archivo, avatar_tipo: 'foto' }));
    }

    function submit(e) {
        e.preventDefault();
        post(route('profile.avatar.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset('foto');
                if (inputFoto.current) inputFoto.current.value = '';
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Perfil</h2>}
        >
            <Head title="Perfil" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                    )}

                    {/* Datos del usuario */}
                    <div className="flex items-center gap-5 bg-white p-6 shadow-sm sm:rounded-lg">
                        <Avatar user={user} tipo={data.avatar_tipo} fotoUrl={fotoUrl} className="h-20 w-20 text-3xl" />
                        <div className="min-w-0">
                            <div className="truncate text-lg font-semibold text-gray-900">{user.name}</div>
                            <div className="truncate text-sm text-gray-500">{user.email}</div>
                            {(esAdmin || rol) && (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {esAdmin && (
                                        <span className="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Administrador</span>
                                    )}
                                    {rol && (
                                        <span className="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{rol}</span>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Imagen de perfil */}
                    <form onSubmit={submit} className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="text-base font-semibold text-gray-900">Imagen de perfil</h3>
                        <p className="mt-1 text-sm text-gray-500">Elige cómo se muestra tu imagen en el sistema.</p>

                        <div className="mt-4 grid grid-cols-3 gap-3">
                            {OPCIONES_AVATAR.map(({ tipo, etiqueta }) => {
                                const activo = data.avatar_tipo === tipo;
                                return (
                                    <button
                                        key={tipo}
                                        type="button"
                                        onClick={() => elegir(tipo)}
                                        aria-pressed={activo}
                                        className={`flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition ${
                                            activo ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        {tipo === 'foto' && !fotoUrl ? (
                                            <span className="flex h-14 w-14 items-center justify-center rounded-full border-2 border-dashed border-gray-300 text-gray-400">
                                                <PhotoIcon className="h-6 w-6" />
                                            </span>
                                        ) : (
                                            <Avatar user={user} tipo={tipo} fotoUrl={fotoUrl} className="h-14 w-14 text-xl" />
                                        )}
                                        <span className={`text-sm font-medium ${activo ? 'text-indigo-700' : 'text-gray-700'}`}>{etiqueta}</span>
                                    </button>
                                );
                            })}
                        </div>

                        <input
                            ref={inputFoto}
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="hidden"
                            onChange={seleccionarFoto}
                        />

                        {data.avatar_tipo === 'foto' && (
                            <div className="mt-3 flex items-center gap-3 text-sm">
                                <button
                                    type="button"
                                    onClick={() => inputFoto.current?.click()}
                                    className="font-medium text-indigo-600 hover:text-indigo-500"
                                >
                                    {fotoUrl ? 'Cambiar foto' : 'Subir foto'}
                                </button>
                                <span className="text-gray-400">JPG, PNG o WebP, máximo 2 MB.</span>
                            </div>
                        )}
                        <InputError message={errors.foto || errors.avatar_tipo} className="mt-2" />

                        <div className="mt-5 flex justify-end">
                            <PrimaryButton disabled={processing || !isDirty}>Guardar</PrimaryButton>
                        </div>
                    </form>

                    {/* Permisos */}
                    <div className="bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="flex items-center gap-2 text-base font-semibold text-gray-900">
                            <ShieldCheckIcon className="h-5 w-5 text-indigo-500" />
                            Permisos
                        </h3>

                        {esAdmin ? (
                            <p className="mt-3 text-sm text-gray-600">Como administrador tienes acceso total a todas las funciones del sistema.</p>
                        ) : secciones.length === 0 ? (
                            <p className="mt-3 text-sm text-gray-500">No tienes permisos asignados.</p>
                        ) : (
                            <div className="mt-4 space-y-5">
                                {secciones.map((seccion) => (
                                    <div key={seccion.etiqueta}>
                                        <h4 className="text-xs font-semibold uppercase tracking-wider text-gray-500">{seccion.etiqueta}</h4>
                                        <ul className="mt-2 divide-y divide-gray-100 rounded-md border border-gray-200">
                                            {seccion.modulos.map((modulo) => (
                                                <li key={modulo.etiqueta} className="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm">
                                                    <span className="text-gray-800">{modulo.etiqueta}</span>
                                                    <span className="flex flex-wrap gap-1">
                                                        {modulo.acciones.map((accion) => (
                                                            <span key={accion} className="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{accion}</span>
                                                        ))}
                                                    </span>
                                                </li>
                                            ))}
                                            {seccion.granulares.map((permiso) => (
                                                <li key={permiso} className="px-3 py-2 text-sm text-gray-800">{permiso}</li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
