import { useMemo, useState } from 'react';

const sinAcentos = (texto) => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

/**
 * Lista de casillas con buscador para elegir varios registros de un catálogo
 * (p. ej. las funciones zootécnicas de una especie). `value` es la lista de
 * ids elegidos.
 */
export default function SeleccionMultiple({ opciones, value, onChange, placeholder = 'Buscar…', vacio = 'Sin coincidencias.' }) {
    const [filtro, setFiltro] = useState('');
    const elegidos = useMemo(() => new Set(value.map(Number)), [value]);

    const visibles = opciones.filter((o) => sinAcentos(o.nombre).includes(sinAcentos(filtro)));

    function alternar(id) {
        const siguiente = new Set(elegidos);
        siguiente.has(id) ? siguiente.delete(id) : siguiente.add(id);
        onChange([...siguiente]);
    }

    // "Todas" / "Ninguna" actúan solo sobre lo que se ve con el filtro actual.
    function marcarVisibles(marcar) {
        const siguiente = new Set(elegidos);
        visibles.forEach((o) => (marcar ? siguiente.add(o.id) : siguiente.delete(o.id)));
        onChange([...siguiente]);
    }

    return (
        <div className="mt-1 rounded-md border border-gray-300">
            <div className="flex items-center gap-3 border-b border-gray-200 p-2">
                <input
                    type="search"
                    value={filtro}
                    onChange={(e) => setFiltro(e.target.value)}
                    placeholder={placeholder}
                    className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <button type="button" onClick={() => marcarVisibles(true)} className="shrink-0 text-xs text-indigo-600 hover:text-indigo-900">
                    Todas
                </button>
                <button type="button" onClick={() => marcarVisibles(false)} className="shrink-0 text-xs text-gray-600 hover:text-gray-900">
                    Ninguna
                </button>
            </div>
            <div className="grid max-h-72 grid-cols-1 gap-x-4 overflow-y-auto p-2 sm:grid-cols-2">
                {visibles.length === 0 && <p className="px-1 py-2 text-sm text-gray-500">{vacio}</p>}
                {visibles.map((o) => (
                    <label key={o.id} className="flex cursor-pointer items-center gap-2 rounded px-1 py-1 text-sm text-gray-700 hover:bg-gray-50">
                        <input
                            type="checkbox"
                            checked={elegidos.has(o.id)}
                            onChange={() => alternar(o.id)}
                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        {o.nombre}
                    </label>
                ))}
            </div>
            <p className="border-t border-gray-200 px-3 py-1.5 text-xs text-gray-500">
                {elegidos.size} seleccionada{elegidos.size === 1 ? '' : 's'}
            </p>
        </div>
    );
}
