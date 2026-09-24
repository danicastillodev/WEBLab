import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';

const checkboxClass =
    'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500';

/**
 * Campos de control de acceso compartidos por Users/Create y Users/Edit:
 * bandera de administrador, ventana horaria + días permitidos, y la matriz
 * de permisos módulo × acción.
 *
 * Cuando el usuario es administrador la matriz y el horario se deshabilitan,
 * porque `User::puede()` y `User::dentroDeHorario()` los ignoran.
 */
export default function AccesoFields({
    data,
    setData,
    errors,
    modulos,
    acciones,
    dias,
    gruposPermisos = {},
}) {
    const esAdmin = data.es_admin;

    function toggleDia(dia) {
        const actuales = data.dias_permitidos ?? [];
        setData(
            'dias_permitidos',
            actuales.includes(dia)
                ? actuales.filter((d) => d !== dia)
                : [...actuales, dia].sort((a, b) => a - b),
        );
    }

    function togglePermiso(modulo, accion) {
        setData('permisos', {
            ...data.permisos,
            [modulo]: {
                ...data.permisos[modulo],
                [accion]: !data.permisos[modulo]?.[accion],
            },
        });
    }

    function toggleModuloCompleto(modulo, valor) {
        const fila = {};
        Object.keys(acciones).forEach((accion) => {
            fila[accion] = valor;
        });
        setData('permisos', { ...data.permisos, [modulo]: fila });
    }

    function togglePermisoGranular(clave) {
        setData('permisos_granulares', {
            ...data.permisos_granulares,
            [clave]: !data.permisos_granulares?.[clave],
        });
    }

    function toggleGrupoGranularCompleto(claves, valor) {
        const cambios = {};
        claves.forEach((clave) => {
            cambios[clave] = valor;
        });
        setData('permisos_granulares', { ...data.permisos_granulares, ...cambios });
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
                    <h3 className="text-sm font-semibold text-gray-900">
                        Horario de acceso
                    </h3>
                    <p className="mt-1 text-xs text-gray-500">
                        Dejar las horas en blanco permite el acceso a cualquier hora. Si la
                        hora final es menor que la inicial, la ventana cruza la medianoche.
                    </p>

                    <div className="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="hora_inicio" value="Hora de inicio" />
                            <input
                                id="hora_inicio"
                                type="time"
                                value={data.hora_inicio}
                                onChange={(e) => setData('hora_inicio', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError message={errors.hora_inicio} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="hora_fin" value="Hora de fin" />
                            <input
                                id="hora_fin"
                                type="time"
                                value={data.hora_fin}
                                onChange={(e) => setData('hora_fin', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError message={errors.hora_fin} className="mt-2" />
                        </div>
                    </div>

                    <div className="mt-4">
                        <InputLabel value="Días permitidos" />
                        <p className="mt-1 text-xs text-gray-500">
                            Sin ningún día marcado se permite el acceso todos los días.
                        </p>
                        <div className="mt-2 flex flex-wrap gap-3">
                            {Object.entries(dias).map(([numero, nombre]) => (
                                <label key={numero} className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={(data.dias_permitidos ?? []).includes(Number(numero))}
                                        onChange={() => toggleDia(Number(numero))}
                                        className={checkboxClass}
                                    />
                                    <span className="text-sm text-gray-700">{nombre}</span>
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.dias_permitidos} className="mt-2" />
                    </div>
                </div>

                <div className="border-t border-gray-200 pt-6">
                    <h3 className="text-sm font-semibold text-gray-900">
                        Funciones permitidas
                    </h3>

                    <div className="mt-4 overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Módulo
                                    </th>
                                    {Object.values(acciones).map((etiqueta) => (
                                        <th
                                            key={etiqueta}
                                            className="px-4 py-2 text-center text-xs font-medium uppercase tracking-wider text-gray-500"
                                        >
                                            {etiqueta}
                                        </th>
                                    ))}
                                    <th className="px-4 py-2" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {Object.entries(modulos).map(([modulo, etiqueta]) => {
                                    const fila = data.permisos[modulo] ?? {};
                                    const todos = Object.keys(acciones).every((a) => fila[a]);

                                    return (
                                        <tr key={modulo}>
                                            <td className="px-4 py-2 text-sm font-medium text-gray-900">
                                                {etiqueta}
                                            </td>
                                            {Object.keys(acciones).map((accion) => (
                                                <td key={accion} className="px-4 py-2 text-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={!!fila[accion]}
                                                        onChange={() => togglePermiso(modulo, accion)}
                                                        className={checkboxClass}
                                                    />
                                                </td>
                                            ))}
                                            <td className="px-4 py-2 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => toggleModuloCompleto(modulo, !todos)}
                                                    className="text-xs text-indigo-600 hover:text-indigo-900"
                                                >
                                                    {todos ? 'Ninguno' : 'Todos'}
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    <InputError message={errors.permisos} className="mt-2" />
                </div>

                {Object.keys(gruposPermisos).length > 0 && (
                    <div className="border-t border-gray-200 pt-6">
                        <h3 className="text-sm font-semibold text-gray-900">
                            Permisos adicionales
                        </h3>

                        <div className="mt-4 space-y-6">
                            {Object.entries(gruposPermisos).map(([grupo, { etiqueta, permisos }]) => {
                                const claves = Object.keys(permisos);
                                const todos = claves.every((clave) => data.permisos_granulares?.[clave]);

                                return (
                                    <div key={grupo}>
                                        <div className="flex items-center justify-between">
                                            <h4 className="text-sm font-medium text-gray-900">{etiqueta}</h4>
                                            <button
                                                type="button"
                                                onClick={() => toggleGrupoGranularCompleto(claves, !todos)}
                                                className="text-xs text-indigo-600 hover:text-indigo-900"
                                            >
                                                {todos ? 'Ninguno' : 'Todos'}
                                            </button>
                                        </div>
                                        <div className="mt-2 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                            {Object.entries(permisos).map(([clave, etiquetaPermiso]) => (
                                                <label key={clave} className="flex items-center gap-2">
                                                    <input
                                                        type="checkbox"
                                                        checked={!!data.permisos_granulares?.[clave]}
                                                        onChange={() => togglePermisoGranular(clave)}
                                                        className={checkboxClass}
                                                    />
                                                    <span className="text-sm text-gray-700">
                                                        {etiquetaPermiso}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        <InputError message={errors.permisos_granulares} className="mt-2" />
                    </div>
                )}
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
