import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useMemo, useState } from 'react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const POR_PAGINA = 20;

const sinAcentos = (texto) => (texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

export const explotacionVacia = (estadoId = '') => ({
    nombre: '',
    direccion: '',
    estado_id: estadoId,
    municipio_id: '',
    caseta: '',
    lote: '',
    parvada: '',
});

/** Pasa una explotación guardada al formato del formulario (ids como texto, nulos como ''). */
export const explotacionDelServidor = (e) => ({
    id: e.id,
    nombre: e.nombre,
    direccion: e.direccion ?? '',
    estado_id: e.estado_id ? String(e.estado_id) : '',
    municipio_id: e.municipio_id ? String(e.municipio_id) : '',
    caseta: e.caseta ?? '',
    lote: e.lote ?? '',
    parvada: e.parvada ?? '',
});

/**
 * Explotaciones de un propietario dentro de su formulario. Hay propietarios
 * con cientos (granjas integradoras), así que no se despliegan todas como las
 * direcciones: se listan en una tabla con buscador y páginas, y se agregan o
 * editan en un modal. `value` es la lista completa que se envía con el
 * propietario.
 */
export default function ExplotacionesPropietario({ value, onChange, estados, municipios, errors = {}, estadoDefault = '' }) {
    const [filtro, setFiltro] = useState('');
    const [pagina, setPagina] = useState(0);
    const [editando, setEditando] = useState(null); // { index, datos } — index null = nueva
    const [errorNombre, setErrorNombre] = useState('');

    const municipioPorId = useMemo(() => new Map(municipios.map((m) => [String(m.id), m.nombre])), [municipios]);
    const estadoPorId = useMemo(() => new Map(estados.map((e) => [String(e.id), e.nombre])), [estados]);

    // Se guarda el índice original para editar y para casar los errores del servidor.
    const filtradas = value
        .map((exp, index) => ({ exp, index }))
        .filter(({ exp }) => {
            const q = sinAcentos(filtro);
            return !q || [exp.nombre, exp.direccion, municipioPorId.get(exp.municipio_id)].some((t) => sinAcentos(t).includes(q));
        });
    const paginas = Math.max(1, Math.ceil(filtradas.length / POR_PAGINA));
    const paginaActual = Math.min(pagina, paginas - 1);
    const visibles = filtradas.slice(paginaActual * POR_PAGINA, (paginaActual + 1) * POR_PAGINA);

    const conError = new Set(
        Object.keys(errors)
            .map((k) => k.match(/^explotaciones\.(\d+)\./)?.[1])
            .filter(Boolean)
            .map(Number),
    );

    function abrir(index = null) {
        setErrorNombre('');
        setEditando({ index, datos: index === null ? explotacionVacia(estadoDefault) : { ...value[index] } });
    }

    function cambiar(campo, valor) {
        setEditando((prev) => ({
            ...prev,
            datos: { ...prev.datos, [campo]: valor, ...(campo === 'estado_id' ? { municipio_id: '' } : {}) },
        }));
    }

    function guardar() {
        if (!editando.datos.nombre.trim()) {
            setErrorNombre('La explotación necesita un nombre.');
            return;
        }
        if (editando.index === null) {
            onChange([...value, editando.datos]);
            // Que la nueva quede a la vista aunque haya un filtro.
            setFiltro('');
            setPagina(Math.floor(value.length / POR_PAGINA));
        } else {
            onChange(value.map((exp, i) => (i === editando.index ? editando.datos : exp)));
        }
        setEditando(null);
    }

    function quitar(index) {
        if (confirm(`¿Quitar la explotación "${value[index].nombre}"? Se borrará al guardar el propietario.`)) {
            onChange(value.filter((_, i) => i !== index));
        }
    }

    const municipiosDelEstado = editando
        ? municipios.filter((m) => String(m.estado_id) === String(editando.datos.estado_id))
        : [];

    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h3 className="text-sm font-medium text-gray-700">
                    Explotaciones <span className="font-normal text-gray-400">({value.length})</span>
                </h3>
                <button type="button" onClick={() => abrir()} className="text-sm text-indigo-600 hover:text-indigo-900">
                    + Agregar explotación
                </button>
            </div>

            {errors.explotaciones && <p className="mb-2 text-sm text-red-600">{errors.explotaciones}</p>}
            {conError.size > 0 && (
                <p className="mb-2 text-sm text-red-600">
                    Revisa las explotaciones marcadas: {[...conError].map((i) => value[i]?.nombre || `#${i + 1}`).join(', ')}.
                </p>
            )}

            {value.length === 0 ? (
                <p className="rounded-md border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-500">
                    Sin explotaciones. Son opcionales; se eligen al capturar una historia clínica.
                </p>
            ) : (
                <div className="rounded-md border border-gray-200">
                    {value.length > POR_PAGINA / 2 && (
                        <div className="border-b border-gray-200 p-2">
                            <input
                                type="search"
                                value={filtro}
                                onChange={(e) => { setFiltro(e.target.value); setPagina(0); }}
                                placeholder="Buscar por nombre, dirección o municipio…"
                                className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    )}
                    <ul className="divide-y divide-gray-100">
                        {visibles.length === 0 && <li className="px-3 py-2 text-sm text-gray-500">Ninguna coincide con la búsqueda.</li>}
                        {visibles.map(({ exp, index }) => (
                            <li key={exp.id ?? `nueva-${index}`} className={`flex items-start justify-between gap-3 px-3 py-2 ${conError.has(index) ? 'bg-red-50' : ''}`}>
                                <div className="min-w-0 text-sm">
                                    <p className="truncate font-medium text-gray-800">
                                        {exp.nombre}
                                        {!exp.id && <span className="ms-2 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-indigo-600">Nueva</span>}
                                    </p>
                                    <p className="truncate text-xs text-gray-500">
                                        {[exp.direccion, municipioPorId.get(exp.municipio_id), estadoPorId.get(exp.estado_id)].filter(Boolean).join(' · ') || 'Sin ubicación'}
                                        {[exp.caseta && `Caseta ${exp.caseta}`, exp.lote && `Lote ${exp.lote}`, exp.parvada && `Parvada ${exp.parvada}`]
                                            .filter(Boolean)
                                            .map((t) => ` · ${t}`)
                                            .join('')}
                                    </p>
                                </div>
                                <div className="flex shrink-0 gap-3 text-xs">
                                    <button type="button" onClick={() => abrir(index)} className="text-indigo-600 hover:text-indigo-900">Editar</button>
                                    <button type="button" onClick={() => quitar(index)} className="text-red-500 hover:text-red-700">Quitar</button>
                                </div>
                            </li>
                        ))}
                    </ul>
                    {paginas > 1 && (
                        <div className="flex items-center justify-between border-t border-gray-200 px-3 py-2 text-xs text-gray-600">
                            <button type="button" disabled={paginaActual === 0} onClick={() => setPagina(paginaActual - 1)} className="disabled:opacity-40">
                                ← Anterior
                            </button>
                            <span>Página {paginaActual + 1} de {paginas} · {filtradas.length} explotaciones</span>
                            <button type="button" disabled={paginaActual >= paginas - 1} onClick={() => setPagina(paginaActual + 1)} className="disabled:opacity-40">
                                Siguiente →
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Sin <form>: el modal vive dentro del formulario del propietario. */}
            <Modal show={editando !== null} onClose={() => setEditando(null)} maxWidth="lg">
                {editando && (
                    <div className="space-y-4 p-6">
                        <h3 className="text-lg font-semibold text-gray-800">
                            {editando.index === null ? 'Nueva explotación' : 'Editar explotación'}
                        </h3>

                        <div>
                            <InputLabel value="Nombre" required />
                            <TextInput
                                value={editando.datos.nombre}
                                onChange={(e) => cambiar('nombre', e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), guardar())}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            <InputError message={errorNombre || errors[`explotaciones.${editando.index}.nombre`]} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel value="Dirección" />
                            <TextInput
                                value={editando.datos.direccion}
                                onChange={(e) => cambiar('direccion', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="Opcional"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Estado" />
                                <select value={editando.datos.estado_id} onChange={(e) => cambiar('estado_id', e.target.value)} className={selectClass}>
                                    <option value="">— Opcional —</option>
                                    {estados.map((e) => <option key={e.id} value={e.id}>{e.nombre}</option>)}
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Municipio" />
                                <select
                                    value={editando.datos.municipio_id}
                                    onChange={(e) => cambiar('municipio_id', e.target.value)}
                                    disabled={!editando.datos.estado_id}
                                    className={selectClass}
                                >
                                    <option value="">— Opcional —</option>
                                    {municipiosDelEstado.map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            {['caseta', 'lote', 'parvada'].map((campo) => (
                                <div key={campo}>
                                    <InputLabel value={campo[0].toUpperCase() + campo.slice(1)} />
                                    <TextInput
                                        value={editando.datos[campo]}
                                        onChange={(e) => cambiar(campo, e.target.value)}
                                        className="mt-1 block w-full"
                                        maxLength={50}
                                        placeholder="Opcional"
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="flex justify-end gap-3 pt-2">
                            <SecondaryButton type="button" onClick={() => setEditando(null)}>Cancelar</SecondaryButton>
                            <PrimaryButton type="button" onClick={guardar}>
                                {editando.index === null ? 'Agregar' : 'Aplicar'}
                            </PrimaryButton>
                        </div>
                        <p className="text-xs text-gray-500">Los cambios se guardan junto con el propietario.</p>
                    </div>
                )}
            </Modal>
        </div>
    );
}
