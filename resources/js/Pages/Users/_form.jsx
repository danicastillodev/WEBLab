import AccesoFields from '@/Components/AccesoFields';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

// Se está dando de alta o editando a OTRO usuario: el navegador no debe
// rellenar estos campos con las credenciales guardadas de quien inició sesión.
// Chrome ignora autocomplete="off" en contraseñas, pero respeta "new-password";
// los data-* hacen lo mismo con 1Password, LastPass y Bitwarden.
const sinAutollenado = {
    'data-1p-ignore': true,
    'data-lpignore': 'true',
    'data-bwignore': true,
};

/** Campos compartidos por las pantallas de alta y edición de usuarios. */
export default function UserForm({
    data,
    setData,
    errors,
    processing,
    submit,
    etiquetaBoton,
    areas,
    modulos,
    secciones,
    acciones,
    dias,
    gruposPermisos,
    roles,
    esEdicion = false,
}) {
    function toggleArea(id) {
        const actuales = data.area_ids ?? [];
        setData(
            'area_ids',
            actuales.includes(id)
                ? actuales.filter((a) => a !== id)
                : [...actuales, id],
        );
    }

    return (
        <form onSubmit={submit} className="space-y-6" autoComplete="off">
            <div>
                <InputLabel htmlFor="name" value="Nombre" />
                <TextInput
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className="mt-1 block w-full"
                    autoFocus
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="username" value="Usuario" />
                <TextInput
                    id="username"
                    value={data.username}
                    onChange={(e) => setData('username', e.target.value)}
                    className="mt-1 block w-full"
                    autoComplete="off"
                    {...sinAutollenado}
                />
                <p className="mt-1 text-xs text-gray-500">
                    Es el nombre con el que se inicia sesión.
                </p>
                <InputError message={errors.username} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.email} className="mt-2" />
            </div>

            <div>
                <InputLabel value="Áreas" />
                <p className="mt-1 text-xs text-gray-500">
                    Un usuario puede pertenecer a varias áreas.
                </p>
                {areas.length === 0 ? (
                    <p className="mt-2 text-sm text-gray-500">Aún no hay áreas.</p>
                ) : (
                    <div className="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                        {areas.map((area) => (
                            <label key={area.id} className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={data.area_ids.includes(area.id)}
                                    onChange={() => toggleArea(area.id)}
                                    className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <span className="text-sm text-gray-700">{area.nombre}</span>
                            </label>
                        ))}
                    </div>
                )}
                <InputError message={errors.area_ids} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="password" value={esEdicion ? 'Nueva contraseña' : 'Contraseña'} />
                <TextInput
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    className="mt-1 block w-full"
                    autoComplete="new-password"
                    {...sinAutollenado}
                    placeholder={esEdicion ? 'Dejar en blanco para mantener la contraseña actual' : ''}
                />
                <InputError message={errors.password} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor="password_confirmation"
                    value={esEdicion ? 'Confirmar nueva contraseña' : 'Confirmar contraseña'}
                />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    className="mt-1 block w-full"
                    autoComplete="new-password"
                    {...sinAutollenado}
                />
                <InputError message={errors.password_confirmation} className="mt-2" />
            </div>

            <AccesoFields
                data={data}
                setData={setData}
                errors={errors}
                modulos={modulos}
                secciones={secciones}
                acciones={acciones}
                dias={dias}
                gruposPermisos={gruposPermisos}
                roles={roles}
            />

            <div className="flex items-center gap-4 border-t border-gray-200 pt-6">
                <PrimaryButton disabled={processing}>{etiquetaBoton}</PrimaryButton>
                <Link href={route('users.index')} className="text-sm text-gray-600 hover:text-gray-900">
                    Cancelar
                </Link>
            </div>
        </form>
    );
}
