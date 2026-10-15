import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import HorarioFields from '@/Components/HorarioFields';
import PermisosPorSeccion from '@/Components/PermisosPorSeccion';

const checkboxClass =
    'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500';

/**
 * Campos de control de acceso compartidos por Users/Create y Users/Edit:
 * bandera de administrador, ventana horaria + días permitidos, el rol que
 * sirve de plantilla y las funciones permitidas (PermisosPorSeccion).
 *
 * Cuando el usuario es administrador la matriz y el horario se deshabilitan,
 * porque `User::puede()` y `User::dentroDeHorario()` los ignoran.
 */
export default function AccesoFields({
    data,
    setData,
    errors,
    modulos,
    secciones = {},
    acciones,
    dias,
    gruposPermisos = {},
    roles = [],
}) {
    const esAdmin = data.es_admin;

    // El rol es una plantilla: copia su horario y sus permisos sobre los del formulario.
    // "Personalizado" sólo quita la referencia y deja horario y permisos como estén.
    function aplicarRol(valor) {
        const rol = roles.find((r) => String(r.id) === valor);

        if (!rol) {
            setData('rol_id', '');
            return;
        }

        setData({
            ...data,
            rol_id: rol.id,
            hora_inicio: rol.hora_inicio,
            hora_fin: rol.hora_fin,
            dias_permitidos: rol.dias_permitidos,
            permisos: rol.permisos,
            permisos_granulares: rol.permisos_granulares,
        });
    }

    return (
        <>
            <div className="border-t border-gray-200 pt-6">
                <label className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={esAdmin}
                        onChange={(e) => setData('es_admin', e.target.checked)}
                        className={checkboxClass}
                    />
                    <span className="text-sm font-medium text-gray-700">
                        Administrador
                    </span>
                </label>
                <p className="mt-1 text-xs text-gray-500">
                    Un administrador omite el control de funciones y el horario de acceso.
                </p>
                <InputError message={errors.es_admin} className="mt-2" />
            </div>

            <fieldset disabled={esAdmin} className={esAdmin ? 'opacity-50' : ''}>
                <div className="border-t border-gray-200 pt-6">
                    <InputLabel htmlFor="rol_id" value="Rol" />
                    <select
                        id="rol_id"
                        value={data.rol_id ?? ''}
                        onChange={(e) => aplicarRol(e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Personalizado</option>
                        {roles.map((rol) => (
                            <option key={rol.id} value={rol.id}>
                                {rol.nombre}
                            </option>
                        ))}
                    </select>
                    <p className="mt-1 text-xs text-gray-500">
                        Al elegir un rol se cargan su horario y sus permisos como
                        plantilla; después puedes ajustarlos para este usuario.
                    </p>
                    <InputError message={errors.rol_id} className="mt-2" />
                </div>

                <HorarioFields data={data} setData={setData} errors={errors} dias={dias} />

                <PermisosPorSeccion
                    data={data}
                    setData={setData}
                    errors={errors}
                    modulos={modulos}
                    secciones={secciones}
                    acciones={acciones}
                    gruposPermisos={gruposPermisos}
                />
            </fieldset>
        </>
    );
}

/**
 * Matriz vacía (todo en false) para inicializar el formulario de creación.
 */
export function matrizVacia(modulos, acciones) {
    const matriz = {};
    Object.keys(modulos).forEach((modulo) => {
        matriz[modulo] = {};
        Object.keys(acciones).forEach((accion) => {
            matriz[modulo][accion] = false;
        });
    });
    return matriz;
}

/**
 * Mapa vacío (todo en false) de permisos granulares, para inicializar el
 * formulario de creación.
 */
export function permisosGranularesVacios(gruposPermisos) {
    const mapa = {};
    Object.values(gruposPermisos).forEach(({ permisos }) => {
        Object.keys(permisos).forEach((clave) => {
            mapa[clave] = false;
        });
    });
    return mapa;
}
