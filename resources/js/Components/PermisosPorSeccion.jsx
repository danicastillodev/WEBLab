import InputError from '@/Components/InputError';
import { useEffect, useMemo, useRef, useState } from 'react';

const checkboxClass =
    'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500';

/** Casilla que además muestra el estado "parcial" (ni todo ni nada). */
function CasillaTriestado({ checked, indeterminate, onChange }) {
    const ref = useRef(null);

    useEffect(() => {
        if (ref.current) ref.current.indeterminate = indeterminate;
    }, [indeterminate]);

    return (
        <input
            ref={ref}
            type="checkbox"
            checked={checked}
            onChange={onChange}
            className={checkboxClass}
        />
    );
}

/**
 * Funciones permitidas agrupadas como el menú lateral (config
 * modulos.secciones): cada sección tiene una casilla que la concede o retira
 * completa, su matriz módulo × acción y sus permisos granulares. Lo usan el
 * formulario de usuarios y el de roles; ambos guardan `permisos` y
 * `permisos_granulares` con el mismo formato.
 */
export default function PermisosPorSeccion({
    data,
    setData,
    errors,
    modulos,
    secciones = {},
    acciones,
    gruposPermisos = {},
}) {
    // Secciones del menú con sus módulos y grupos granulares. Lo que no esté
    // asignado a ninguna sección cae en "Otros" para que nunca quede oculto.
    const seccionesFormulario = useMemo(() => {
        const lista = Object.entries(secciones).map(([clave, seccion]) => ({
            clave,
            etiqueta: seccion.etiqueta,
            modulos: (seccion.modulos ?? []).filter((m) => m in modulos),
            grupos: (seccion.grupos_granulares ?? []).filter((g) => g in gruposPermisos),
        }));

        const usadosModulos = new Set(lista.flatMap((s) => s.modulos));
        const usadosGrupos = new Set(lista.flatMap((s) => s.grupos));
        const otros = {
            clave: 'otros',
            etiqueta: 'Otros',
            modulos: Object.keys(modulos).filter((m) => !usadosModulos.has(m)),
            grupos: Object.keys(gruposPermisos).filter((g) => !usadosGrupos.has(g)),
        };

        return [...lista, otros].filter((s) => s.modulos.length || s.grupos.length);
    }, [secciones, modulos, gruposPermisos]);

    const [abiertas, setAbiertas] = useState({});

    function clavesGranularesDe(seccion) {
        return seccion.grupos.flatMap((g) => Object.keys(gruposPermisos[g].permisos));
    }

    function estadoSeccion(seccion) {
        const valores = [
            ...seccion.modulos.flatMap((m) =>
                Object.keys(acciones).map((a) => !!data.permisos[m]?.[a]),
            ),
            ...clavesGranularesDe(seccion).map((c) => !!data.permisos_granulares?.[c]),
        ];

        if (valores.every(Boolean)) return 'todos';
        if (valores.some(Boolean)) return 'parcial';
        return 'ninguno';
    }

    // Una sola llamada a setData por campo: dos llamadas seguidas con el mismo
    // objeto `data` se pisarían entre sí.
    function toggleSeccion(seccion, valor) {
        const permisos = { ...data.permisos };
        seccion.modulos.forEach((modulo) => {
            permisos[modulo] = Object.fromEntries(Object.keys(acciones).map((a) => [a, valor]));
        });

        const granulares = { ...data.permisos_granulares };
        clavesGranularesDe(seccion).forEach((clave) => {
            granulares[clave] = valor;
        });

        setData({ ...data, permisos, permisos_granulares: granulares });
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
        <div className="border-t border-gray-200 pt-6">
            <h3 className="text-sm font-semibold text-gray-900">
                Funciones permitidas
            </h3>
            <p className="mt-1 text-xs text-gray-500">
                Agrupadas como en el menú. La casilla de cada sección concede o
                retira todas sus funciones de un solo clic.
            </p>

            <div className="mt-4 space-y-4">
                {seccionesFormulario.map((seccion) => {
                    const estado = estadoSeccion(seccion);
                    const abierta = abiertas[seccion.clave] ?? false;

                    return (
                        <div key={seccion.clave} className="rounded-md border border-gray-200">
                            <div className="flex items-center justify-between gap-3 bg-gray-50 px-4 py-2">
                                <label className="flex items-center gap-2">
                                    <CasillaTriestado
                                        checked={estado === 'todos'}
                                        indeterminate={estado === 'parcial'}
                                        onChange={() => toggleSeccion(seccion, estado !== 'todos')}
                                    />
                                    <span className="text-sm font-semibold text-gray-900">
                                        {seccion.etiqueta}
                                    </span>
                                </label>
                                <button
                                    type="button"
                                    onClick={() => setAbiertas({ ...abiertas, [seccion.clave]: !abierta })}
                                    className="text-xs text-indigo-600 hover:text-indigo-900"
                                >
                                    {abierta ? 'Ocultar detalle' : 'Ver detalle'}
                                </button>
                            </div>

                            {abierta && (
                                <div className="space-y-4 px-4 py-3">
                                    {seccion.modulos.length > 0 && (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead>
                                                    <tr>
                                                        <th className="py-2 pr-4 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
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
                                                        <th className="py-2" />
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100">
                                                    {seccion.modulos.map((modulo) => {
                                                        const fila = data.permisos[modulo] ?? {};
                                                        const todos = Object.keys(acciones).every((a) => fila[a]);

                                                        return (
                                                            <tr key={modulo}>
                                                                <td className="py-2 pr-4 text-sm text-gray-900">
                                                                    {modulos[modulo]}
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
                                                                <td className="py-2 text-right">
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
                                    )}

                                    {seccion.grupos.map((grupo) => {
                                        const { etiqueta, permisos } = gruposPermisos[grupo];
                                        const claves = Object.keys(permisos);
                                        const todos = claves.every((clave) => data.permisos_granulares?.[clave]);

                                        return (
                                            <div key={grupo}>
                                                <div className="flex items-center justify-between">
                                                    <h4 className="text-xs font-medium uppercase tracking-wider text-gray-500">
                                                        {seccion.modulos.length > 0 ? 'Permisos adicionales' : etiqueta}
                                                    </h4>
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
                            )}
                        </div>
                    );
                })}
            </div>
            <InputError message={errors.permisos} className="mt-2" />
            <InputError message={errors.permisos_granulares} className="mt-2" />
        </div>
    );
}
