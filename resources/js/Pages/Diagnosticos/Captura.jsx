import DatePickerInput from '@/Components/DatePickerInput';
import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SparklesIcon } from '@heroicons/react/24/outline';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { EstadoAnalisis } from './_estados';

const ETIQUETAS_RESULTADO = { negativo: 'Negativo', positivo: 'Positivo', sospechoso: 'Sospechoso' };

const filaVacia = () => ({ cantidad: '', identificacion: '', resultado: '', aretes: '' });
const estaVacia = (m) => !m.cantidad && !m.identificacion && !m.resultado && !m.aretes;

const escaparHtml = (texto) => String(texto)
    .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

/**
 * Una línea de resultado por muestra capturada:
 * "3 SUERO SANGUINEO negativos a Brucella por la prueba de Tarjeta".
 * El resultado va en plural cuando la cantidad es mayor a 1.
 */
function lineaResultado(m, bacteria, prueba) {
    const cantidad = parseInt(m.cantidad, 10);
    const resultado = cantidad > 1 ? `${m.resultado}s` : m.resultado;
    return `${cantidad} ${m.identificacion} ${resultado} a ${bacteria} por la prueba de ${prueba}`;
}

/**
 * Aretes como lista, separados por espacios, comas o punto y coma:
 * "12334 12335 12336" → "[12334, 12335, 12336]". Vacío si no se capturaron.
 */
const listaAretes = (aretes) => String(aretes ?? '').split(/[\s,;]+/).filter(Boolean);

function aretesEnLinea(aretes) {
    const lista = listaAretes(aretes);
    return lista.length ? `[${lista.join(', ')}]` : '';
}

/** "2026-08-25" (o con hora) → "25/08/2026". */
function formatFecha(valor) {
    if (!valor) return '—';
    const [y, m, d] = String(valor).slice(0, 10).split('-');
    return y && m && d ? `${d}/${m}/${y}` : String(valor);
}

/** "2026-08-25" → "2026/08/25", el formato del DatePickerInput. */
const aPicker = (valor) => (valor ? String(valor).slice(0, 10).replaceAll('-', '/') : '');

const hoy = () => new Date().toLocaleDateString('sv').replaceAll('-', '/');

function Campo({ etiqueta, children }) {
    return (
        <div>
            <dt className="text-xs font-medium uppercase tracking-wider text-gray-500">{etiqueta}</dt>
            <dd className="mt-0.5 text-sm text-gray-900">{children ?? '—'}</dd>
        </div>
    );
}

/**
 * Editor de texto enriquecido mínimo (negritas, cursivas, subrayado y
 * listas) sobre contentEditable. El HTML se limpia en el servidor; aquí sólo
 * se cuenta el texto sin formato contra el máximo.
 */
function EditorResultado({ valor, onChange, maximo, deshabilitado }) {
    const ref = useRef(null);
    const [largo, setLargo] = useState(0);

    // El contenido inicial se pone una sola vez: reescribir innerHTML en cada
    // tecla movería el cursor al inicio.
    useEffect(() => {
        if (ref.current) {
            ref.current.innerHTML = valor ?? '';
            setLargo(ref.current.innerText.trim().length);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    function alEscribir() {
        setLargo(ref.current.innerText.trim().length);
        onChange(ref.current.innerHTML);
    }

    function formato(comando) {
        ref.current.focus();
        document.execCommand(comando);
        alEscribir();
    }

    const boton = 'rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-200 disabled:opacity-40';

    return (
        <div className="rounded-md border border-gray-300 shadow-sm">
            <div className="flex items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-1">
                <button type="button" disabled={deshabilitado} onMouseDown={(e) => e.preventDefault()} onClick={() => formato('bold')} className={`${boton} font-bold`} title="Negritas">B</button>
                <button type="button" disabled={deshabilitado} onMouseDown={(e) => e.preventDefault()} onClick={() => formato('italic')} className={`${boton} italic`} title="Cursivas">I</button>
                <button type="button" disabled={deshabilitado} onMouseDown={(e) => e.preventDefault()} onClick={() => formato('underline')} className={`${boton} underline`} title="Subrayado">U</button>
                <span className="mx-1 h-4 w-px bg-gray-300" aria-hidden="true" />
                <button type="button" disabled={deshabilitado} onMouseDown={(e) => e.preventDefault()} onClick={() => formato('insertUnorderedList')} className={boton} title="Lista con viñetas">• Lista</button>
                <button type="button" disabled={deshabilitado} onMouseDown={(e) => e.preventDefault()} onClick={() => formato('insertOrderedList')} className={boton} title="Lista numerada">1. Lista</button>
                <span className={`ml-auto text-xs ${largo > maximo ? 'font-semibold text-red-600' : 'text-gray-500'}`}>
                    {largo}/{maximo}
                </span>
            </div>
            <div
                ref={ref}
                contentEditable={!deshabilitado}
                suppressContentEditableWarning
                onInput={alEscribir}
                className={`prose-sm min-h-[8rem] px-3 py-2 text-sm focus:outline-none [&_ol]:list-decimal [&_ol]:pl-5 [&_ul]:list-disc [&_ul]:pl-5 ${deshabilitado ? 'bg-gray-50 text-gray-600' : ''}`}
            />
        </div>
    );
}

export default function Captura({ analisis, diagnostico, resultados, maximoResultado, permisos }) {
    const { flash } = usePage().props;
    const historia = analisis.historia_clinica;
    const tipoMuestra = analisis.tipo_muestra?.nombre ?? '';
    const editable = permisos.guardar || permisos.emitir;

    const { data, setData, put, transform, processing, errors, isDirty } = useForm({
        fecha_realizacion: aPicker(diagnostico?.fecha_realizacion) || hoy(),
        // Un diagnóstico nuevo arranca con una fila lista para capturar.
        muestras: diagnostico?.muestras?.length
            ? diagnostico.muestras.map(({ cantidad, identificacion, resultado, aretes }) => ({
                cantidad: String(cantidad), identificacion, resultado, aretes: aretes ?? '',
            }))
            : editable ? [filaVacia()] : [],
        resultado: diagnostico?.resultado ?? '',
    });

    const capturadas = data.muestras.reduce((suma, m) => suma + (parseInt(m.cantidad, 10) || 0), 0);

    function cambiarMuestra(i, campo, valor) {
        setData('muestras', data.muestras.map((m, j) => {
            if (j !== i) return m;
            const nueva = { ...m, [campo]: valor };
            // Al escribir la cantidad, la identificación se llena con el tipo
            // de muestra de la historia (ej. "SUERO SANGUINEO") si está vacía.
            if (campo === 'cantidad' && valor !== '' && !m.identificacion) {
                nueva.identificacion = tipoMuestra;
            }
            return nueva;
        }));
    }

    const agregarMuestra = () => setData('muestras', [...data.muestras, filaVacia()]);
    const quitarMuestra = (i) => setData('muestras', data.muestras.filter((_, j) => j !== i));

    function enviar(accion) {
        // Las filas que quedaron sin tocar no se mandan, para que guardar sin
        // capturar muestras no falle por la fila vacía inicial.
        transform((d) => ({ ...d, accion, muestras: d.muestras.filter((m) => !estaVacia(m)) }));
        put(route('diagnosticos.guardar', analisis.id), { preserveScroll: true });
    }

    // Bacteria y prueba sólo sirven para armar el texto del resultado; no se
    // guardan. Al generar, el editor se vuelve a montar con el texto nuevo.
    const [bacteria, setBacteria] = useState('');
    const [prueba, setPrueba] = useState('');
    const [versionEditor, setVersionEditor] = useState(0);

    const filasCompletas = data.muestras.filter((m) => parseInt(m.cantidad, 10) > 0 && m.identificacion && m.resultado);
    const puedeGenerar = bacteria.trim() !== '' && prueba.trim() !== '' && filasCompletas.length > 0;

    function generarResultado() {
        const textoActual = data.resultado.replace(/<[^>]*>/g, '').trim();
        if (textoActual && !confirm('Se reemplazará el texto actual del resultado. ¿Continuar?')) return;

        const html = filasCompletas
            // Cada muestra: su línea de resultado y, debajo, sus aretes si los tiene.
            .flatMap((m) => [lineaResultado(m, bacteria.trim(), prueba.trim()), aretesEnLinea(m.aretes)])
            .filter(Boolean)
            .map(escaparHtml)
            .join('<br>');
        setData('resultado', html);
        setVersionEditor((v) => v + 1);
    }

    function cancelar() {
        if (confirm('¿Cancelar este análisis? Ya no podrá capturarse su diagnóstico.')) {
            router.patch(route('diagnosticos.cancelar', analisis.id));
        }
    }

    // La fecha de toma es el mínimo de la realización.
    const minimo = historia?.fecha_muestra ? new Date(`${String(historia.fecha_muestra).slice(0, 10)}T00:00:00`) : undefined;

    const celda = 'block w-full rounded-md border border-gray-300 px-2 py-1 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand disabled:bg-gray-50';
    const th = 'px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500';
    const erroresMuestras = Object.entries(errors).filter(([k]) => k.startsWith('muestras.'));

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Diagnóstico — Caso {historia?.numero_caso ?? '—'}</h2>}
        >
            <Head title={`Diagnóstico caso ${historia?.numero_caso ?? ''}`} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{flash.success}</div>
                    )}

                    {/* Datos fijos de la historia clínica */}
                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="border-b border-gray-200 pb-2 text-base font-semibold text-gray-800">Información de historia clínica</h3>
                        <dl className="mt-3 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                            <Campo etiqueta="Caso">{historia?.numero_caso}</Campo>
                            <Campo etiqueta="Fecha de recepción">{formatFecha(historia?.fecha_recepcion)}</Campo>
                            <Campo etiqueta="Fecha de toma">{formatFecha(historia?.fecha_muestra)}</Campo>
                            <div className="sm:col-span-2">
                                <Campo etiqueta="Análisis">
                                    {analisis.servicio?.siglas && <span className="mr-1 text-gray-500">{analisis.servicio.siglas}</span>}
                                    {analisis.servicio?.descripcion}
                                </Campo>
                            </div>
                            <Campo etiqueta="Especie">{historia?.especie?.nombre}</Campo>
                        </dl>
                        <div className="mt-4">
                            <p className="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500">Muestras</p>
                            <table className="min-w-full divide-y divide-gray-200 rounded-md border border-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className={th}>Cant.</th>
                                        <th className={th}>Tipo</th>
                                        <th className={th}>Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td className="px-3 py-2 text-sm">{analisis.cantidad}</td>
                                        <td className="px-3 py-2 text-sm">{tipoMuestra || '—'}</td>
                                        <td className="px-3 py-2 text-sm text-gray-500">{analisis.notas || '—'}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {/* Datos a capturar */}
                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <h3 className="border-b border-gray-200 pb-2 text-base font-semibold text-gray-800">Información de diagnóstico</h3>

                        <div className="mt-4 max-w-xs">
                            <label htmlFor="fecha_realizacion" className="mb-1 block text-xs font-medium text-gray-600">Fecha de realización</label>
                            {editable ? (
                                <DatePickerInput
                                    id="fecha_realizacion"
                                    value={data.fecha_realizacion}
                                    onChange={(v) => setData('fecha_realizacion', v)}
                                    minDate={minimo}
                                    className="w-full px-3 py-1.5 text-sm"
                                />
                            ) : (
                                <p className="text-sm text-gray-900">{formatFecha(diagnostico?.fecha_realizacion)}</p>
                            )}
                            <InputError message={errors.fecha_realizacion} className="mt-1" />
                        </div>

                        <div className="mt-6">
                            <div className="mb-1 flex items-center justify-between">
                                <p className="text-xs font-medium uppercase tracking-wider text-gray-500">Muestras</p>
                                <span className={`text-xs ${capturadas > analisis.cantidad ? 'font-semibold text-red-600' : 'text-gray-500'}`}>
                                    {capturadas} de {analisis.cantidad}
                                </span>
                            </div>
                            <table className="min-w-full divide-y divide-gray-200 rounded-md border border-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className={`${th} w-24`}>Cantidad</th>
                                        <th className={th}>Ident.</th>
                                        <th className={`${th} w-40`}>Resultado</th>
                                        <th className={th}>Aretes</th>
                                        {editable && <th className="w-10" />}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {data.muestras.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-3 py-3 text-center text-sm text-gray-500">Sin muestras capturadas.</td>
                                        </tr>
                                    )}
                                    {data.muestras.map((m, i) => (
                                        <tr key={i}>
                                            <td className="px-3 py-2">
                                                <input type="number" min="1" value={m.cantidad} disabled={!editable}
                                                    onChange={(e) => cambiarMuestra(i, 'cantidad', e.target.value)} className={celda} />
                                            </td>
                                            <td className="px-3 py-2">
                                                <input type="text" maxLength={150} value={m.identificacion} disabled={!editable}
                                                    onChange={(e) => cambiarMuestra(i, 'identificacion', e.target.value)} className={celda} />
                                            </td>
                                            <td className="px-3 py-2">
                                                <select value={m.resultado} disabled={!editable}
                                                    onChange={(e) => cambiarMuestra(i, 'resultado', e.target.value)} className={celda}>
                                                    <option value="">—</option>
                                                    {resultados.map((r) => <option key={r} value={r}>{ETIQUETAS_RESULTADO[r] ?? r}</option>)}
                                                </select>
                                            </td>
                                            <td className="px-3 py-2">
                                                <input type="text" maxLength={255} value={m.aretes} disabled={!editable}
                                                    onChange={(e) => cambiarMuestra(i, 'aretes', e.target.value)} className={celda} />
                                                {(() => {
                                                    // Si hay aretes, tiene que haber uno por muestra de la fila.
                                                    const aretes = listaAretes(m.aretes).length;
                                                    const cantidad = parseInt(m.cantidad, 10) || 0;
                                                    return aretes > 0 && aretes !== cantidad ? (
                                                        <p className="mt-0.5 text-xs text-red-600">{aretes} aretes para {cantidad} muestras</p>
                                                    ) : null;
                                                })()}
                                            </td>
                                            {editable && (
                                                <td className="px-2 py-2 text-right">
                                                    <button type="button" onClick={() => quitarMuestra(i)} className="text-red-600 hover:text-red-900" title="Quitar">✕</button>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {editable && (
                                <button
                                    type="button"
                                    onClick={agregarMuestra}
                                    disabled={capturadas >= analisis.cantidad}
                                    className="mt-2 rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                                >
                                    Agregar muestra
                                </button>
                            )}
                            <InputError message={errors.muestras} className="mt-1" />
                            {erroresMuestras.length > 0 && (
                                <InputError message={`Fila ${Number(erroresMuestras[0][0].split('.')[1]) + 1}: ${erroresMuestras[0][1]}`} className="mt-1" />
                            )}
                        </div>

                        <div className="mt-6">
                            <p className="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500">Resultado</p>
                            {editable && (
                                <div className="mb-2 flex flex-wrap items-end gap-2">
                                    <div className="min-w-[10rem] flex-1">
                                        <label htmlFor="bacteria" className="mb-1 block text-xs font-medium text-gray-600">Bacteria</label>
                                        <input id="bacteria" type="text" value={bacteria} onChange={(e) => setBacteria(e.target.value)}
                                            placeholder="Ej. Brucella abortus" className={celda} />
                                    </div>
                                    <div className="min-w-[10rem] flex-1">
                                        <label htmlFor="prueba" className="mb-1 block text-xs font-medium text-gray-600">Prueba</label>
                                        <input id="prueba" type="text" value={prueba} onChange={(e) => setPrueba(e.target.value)}
                                            placeholder="Ej. Tarjeta" className={celda} />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={generarResultado}
                                        disabled={!puedeGenerar}
                                        title={puedeGenerar ? 'Generar el texto del resultado' : 'Captura bacteria, prueba y al menos una muestra completa'}
                                        aria-label="Generar el texto del resultado"
                                        className="rounded-md bg-brand p-2 text-white hover:bg-brand-hover disabled:opacity-40"
                                    >
                                        <SparklesIcon className="h-5 w-5" aria-hidden="true" />
                                    </button>
                                </div>
                            )}
                            <EditorResultado
                                key={versionEditor}
                                valor={data.resultado}
                                onChange={(html) => setData('resultado', html)}
                                maximo={maximoResultado}
                                deshabilitado={!editable}
                            />
                            <InputError message={errors.resultado} className="mt-1" />
                        </div>

                        <div className="mt-6 flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4">
                            <div className="flex flex-col items-start gap-3">
                                <div className="flex flex-wrap items-center gap-3">
                                    <span className="text-sm text-gray-600">Status:</span>
                                    <EstadoAnalisis estado={diagnostico?.estado} />
                                    {diagnostico?.creado_por && (
                                        <span className="text-xs text-gray-500">Capturó: {diagnostico.creado_por.name}</span>
                                    )}
                                </div>
                                <Link
                                    href={route('diagnosticos.index')}
                                    onClick={(e) => {
                                        // Sólo se advierte si hay cambios sin guardar.
                                        if (editable && isDirty && !confirm('La información capturada no se guardará. ¿Deseas regresar de todos modos?')) {
                                            e.preventDefault();
                                        }
                                    }}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Regresar
                                </Link>
                            </div>

                            <div className="ml-auto flex flex-wrap gap-2">
                                {permisos.emitir && (
                                    <button type="button" onClick={() => enviar('emitir')} disabled={processing}
                                        className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover disabled:opacity-50">
                                        Emitir
                                    </button>
                                )}
                                {permisos.guardar && (
                                    <button type="button" onClick={() => enviar('guardar')} disabled={processing}
                                        className="rounded-md border border-brand px-4 py-2 text-sm font-medium text-brand hover:bg-indigo-50 disabled:opacity-50">
                                        Guardar
                                    </button>
                                )}
                                {permisos.cancelar && (
                                    <button type="button" onClick={cancelar} disabled={processing}
                                        className="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50">
                                        Cancelar
                                    </button>
                                )}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
