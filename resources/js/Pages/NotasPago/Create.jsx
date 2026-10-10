import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FieldActions from '@/Components/FieldActions';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

function dinero(valor) {
    return `$${Number(valor || 0).toFixed(2)}`;
}

function formatDireccion(dir) {
    if (!dir) return '';
    return `${dir.calle} ${dir.numero_exterior}, ${dir.colonia}`;
}

function Modal({ title, onClose, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-800">{title}</h3>
                    <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                {children}
            </div>
        </div>
    );
}

export default function Create({
    propietarios: initPropietarios,
    direcciones: initDirecciones,
    estados,
    municipios,
    conceptos_opciones: conceptosOpciones,
    tipos_pago: tiposPago,
    iva_porcentaje: ivaPorcentaje,
    historias_preseleccionadas: historiasPreseleccionadas,
}) {
    const { flash } = usePage().props;
    const [propietarios, setPropietarios] = useState(initPropietarios);
    const [direcciones, setDirecciones] = useState(initDirecciones);

    const { data, setData, post, processing, errors, reset } = useForm({
        propietario_id: '',
        direccion_id: '',
        historia_clinica_ids: [],
        concepto: conceptosOpciones[0] ?? '',
        tipo_pago: tiposPago[0] ?? '',
        pagada: false,
        notas_adicionales: '',
    });

    // Los conceptos sólo se agregan automáticamente al seleccionar historias
    // clínicas (ver "Seleccionar Historias Clínicas" más abajo) y no son
    // editables: el servidor los recalcula de la misma forma al guardar, así
    // que esto es sólo una vista previa.
    const [conceptos, setConceptos] = useState(historiasPreseleccionadas?.conceptos ?? []);
    const [historiasSeleccionadas, setHistoriasSeleccionadas] = useState(historiasPreseleccionadas?.historias ?? []); // [{id, numero_caso}]

    // Al llegar desde "¿Desea crear una nota de pago?" (alta de historia
    // clínica), el servidor ya resolvió qué historias del día de hoy, del
    // usuario actual y sin nota de pago, se preseleccionan: sólo falta
    // reflejarlas en el formulario una vez.
    useEffect(() => {
        if (!historiasPreseleccionadas) return;

        setData((prev) => ({
            ...prev,
            propietario_id: prev.propietario_id || String(historiasPreseleccionadas.propietario_id ?? ''),
            direccion_id: prev.direccion_id || (historiasPreseleccionadas.direccion_id ? String(historiasPreseleccionadas.direccion_id) : ''),
            historia_clinica_ids: [...new Set([...prev.historia_clinica_ids, ...historiasPreseleccionadas.historias.map((h) => h.id)])],
        }));
        // Sólo debe aplicarse una vez, al cargar la página con la preselección.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const propietarioSel = propietarios.find((p) => String(p.id) === String(data.propietario_id));
    const direccionSel = direcciones.find((d) => String(d.id) === String(data.direccion_id));
    const direccionesPropietario = direcciones.filter(
        (d) => String(d.propietario_id) === String(data.propietario_id),
    );

    function onPropietarioChange(id) {
        setData((prev) => ({ ...prev, propietario_id: id, direccion_id: '' }));
    }

    function quitarHistoriaSeleccionada(historiaId) {
        setConceptos((prev) => prev.filter((c) => c.historia_clinica_id !== historiaId));
        setHistoriasSeleccionadas((prev) => prev.filter((h) => h.id !== historiaId));
        setData('historia_clinica_ids', data.historia_clinica_ids.filter((id) => id !== historiaId));
    }

    const conceptosCalculados = conceptos.map((c) => {
        const importe = Number(c.cantidad || 0) * Number(c.precio_unitario || 0);
        const iva = importe * (Number(ivaPorcentaje) / 100);
        return { ...c, importe, iva };
    });

    const subtotal = conceptosCalculados.reduce((acc, c) => acc + c.importe, 0);
    const ivaMonto = conceptosCalculados.reduce((acc, c) => acc + c.iva, 0);
    const total = subtotal + ivaMonto;

    function submit(e) {
        e.preventDefault();
        post(route('notas-pago.store'), {
            onSuccess: () => {
                reset();
                setConceptos([]);
                setHistoriasSeleccionadas([]);
            },
        });
    }

    /* ── Seleccionar Historias Clínicas ── */
    const [showSelectorHistorias, setShowSelectorHistorias] = useState(false);
    const [busquedaHistorias, setBusquedaHistorias] = useState('');
    const [resultadosHistorias, setResultadosHistorias] = useState([]);
    const [seleccionHistorias, setSeleccionHistorias] = useState([]); // [{id, numero_caso, propietario}]
    const [buscandoHistorias, setBuscandoHistorias] = useState(false);
    const [aplicandoHistorias, setAplicandoHistorias] = useState(false);
    const [errorHistorias, setErrorHistorias] = useState('');

    function abrirSelectorHistorias() {
        setBusquedaHistorias('');
        setResultadosHistorias([]);
        setSeleccionHistorias([]);
        setErrorHistorias('');
        setShowSelectorHistorias(true);
        buscarHistorias('');
    }

    async function buscarHistorias(texto) {
        setBuscandoHistorias(true);
        setErrorHistorias('');
        try {
            const { data: resultado } = await axios.get(route('notas-pago.buscar-historias'), {
                params: { buscar: texto },
            });
            setResultadosHistorias(resultado.historias);
        } catch (err) {
            setErrorHistorias('No se pudo buscar historias clínicas.');
        } finally {
            setBuscandoHistorias(false);
        }
    }

    // Historias que ya forman parte de la nota (no tiene caso volver a
    // ofrecerlas en los resultados de búsqueda del selector).
    const idsYaAgregados = new Set(historiasSeleccionadas.map((h) => h.id));
    const resultadosDisponibles = resultadosHistorias.filter((h) => !idsYaAgregados.has(h.id));

    function toggleHistoriaSeleccionada(historia) {
        setSeleccionHistorias((prev) => (
            prev.some((h) => h.id === historia.id)
                ? prev.filter((h) => h.id !== historia.id)
                : [...prev, historia]
        ));
    }

    async function confirmarSelectorHistorias() {
        if (seleccionHistorias.length === 0) return;
        setAplicandoHistorias(true);
        setErrorHistorias('');
        try {
            const { data: resultado } = await axios.get(route('notas-pago.conceptos-de-historias'), {
                params: { historia_clinica_ids: seleccionHistorias.map((h) => h.id) },
            });

            setConceptos((prev) => [...prev, ...resultado.conceptos]);
            setHistoriasSeleccionadas((prev) => [...prev, ...resultado.historias]);
            setData((prev) => ({
                ...prev,
                propietario_id: prev.propietario_id || String(resultado.propietario_id ?? ''),
                direccion_id: prev.direccion_id || (resultado.direccion_id ? String(resultado.direccion_id) : ''),
                historia_clinica_ids: [...new Set([...prev.historia_clinica_ids, ...resultado.historias.map((h) => h.id)])],
            }));
            setShowSelectorHistorias(false);
        } catch (err) {
            setErrorHistorias(err.response?.data?.message ?? 'No se pudieron obtener los conceptos de las historias seleccionadas.');
        } finally {
            setAplicandoHistorias(false);
        }
    }

    /* ── Propietario modal ── */
    const [showPropModal, setShowPropModal] = useState(false);
    const [propForm, setPropForm] = useState({ nombre: '', apellidos: '', curp: '', rfc: '', telefono: '' });
    const [propErrors, setPropErrors] = useState({});
    const [propSaving, setPropSaving] = useState(false);

    function openPropModal() {
        setPropForm({ nombre: '', apellidos: '', curp: '', rfc: '', telefono: '' });
        setPropErrors({});
        setShowPropModal(true);
    }

    async function submitPropietario(e) {
        e.preventDefault();
        setPropSaving(true);
        setPropErrors({});
        try {
            const { data: nuevo } = await axios.post(route('historias-clinicas.store-propietario'), propForm);
            setPropietarios((prev) => [...prev, nuevo].sort((a, b) => a.apellidos.localeCompare(b.apellidos)));
            setData((prev) => ({ ...prev, propietario_id: String(nuevo.id), direccion_id: '' }));
            setShowPropModal(false);
        } catch (err) {
            if (err.response?.status === 422) setPropErrors(err.response.data.errors);
        } finally {
            setPropSaving(false);
        }
    }

    /* ── Dirección modal ── */
    const DIR_VACIA = { calle: '', numero_exterior: '', numero_interior: '', colonia: '', estado_id: '', municipio_id: '', codigo_postal: '' };
    const [showDirModal, setShowDirModal] = useState(false);
    const [dirForm, setDirForm] = useState(DIR_VACIA);
    const [dirErrors, setDirErrors] = useState({});
    const [dirSaving, setDirSaving] = useState(false);

    const municipiosFiltrados = municipios.filter((m) => String(m.estado_id) === String(dirForm.estado_id));

    function openDirModal() {
        setDirForm(DIR_VACIA);
        setDirErrors({});
        setShowDirModal(true);
    }

    async function submitDireccion(e) {
        e.preventDefault();
        setDirSaving(true);
        setDirErrors({});
        try {
            const payload = { ...dirForm, propietario_id: data.propietario_id };
            const { data: nueva } = await axios.post(route('historias-clinicas.store-direccion'), payload);
            setDirecciones((prev) => [...prev, nueva]);
            setData((prev) => ({ ...prev, direccion_id: String(nueva.id) }));
            setShowDirModal(false);
        } catch (err) {
            if (err.response?.status === 422) setDirErrors(err.response.data.errors);
        } finally {
            setDirSaving(false);
        }
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Nueva nota de pago</h2>}
        >
            <Head title="Nueva nota de pago" />

            {/* ── Propietario modal ── */}
            {showPropModal && (
                <Modal title="Nuevo cliente" onClose={() => setShowPropModal(false)}>
                    <form onSubmit={submitPropietario} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={propForm.nombre}
                                onChange={(e) => setPropForm((p) => ({ ...p, nombre: e.target.value }))}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {propErrors.nombre && <p className="mt-1 text-sm text-red-600">{propErrors.nombre[0]}</p>}
                        </div>
                        <div>
                            <InputLabel value="Apellidos" />
                            <TextInput
                                value={propForm.apellidos}
                                onChange={(e) => setPropForm((p) => ({ ...p, apellidos: e.target.value }))}
                                className="mt-1 block w-full"
                            />
                            {propErrors.apellidos && <p className="mt-1 text-sm text-red-600">{propErrors.apellidos[0]}</p>}
                        </div>
                        <div>
                            <InputLabel value="Teléfono" />
                            <TextInput
                                value={propForm.telefono}
                                onChange={(e) => setPropForm((p) => ({ ...p, telefono: e.target.value.replace(/\D/g, '') }))}
                                className="mt-1 block w-full"
                                inputMode="numeric"
                                maxLength={10}
                                placeholder="Opcional — 10 dígitos"
                            />
                            {propErrors.telefono && <p className="mt-1 text-sm text-red-600">{propErrors.telefono[0]}</p>}
                        </div>
                        <div>
                            <InputLabel value="RFC" />
                            <TextInput
                                value={propForm.rfc}
                                onChange={(e) => setPropForm((p) => ({ ...p, rfc: e.target.value.toUpperCase() }))}
                                className="mt-1 block w-full font-mono uppercase"
                                maxLength={13}
                                placeholder="Opcional"
                            />
                            {propErrors.rfc && <p className="mt-1 text-sm text-red-600">{propErrors.rfc[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowPropModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={propSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Dirección modal ── */}
            {showDirModal && (
                <Modal title="Nueva dirección" onClose={() => setShowDirModal(false)}>
                    <form onSubmit={submitDireccion} className="space-y-4">
                        <div>
                            <InputLabel value="Calle" />
                            <TextInput
                                value={dirForm.calle}
                                onChange={(e) => setDirForm((p) => ({ ...p, calle: e.target.value }))}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {dirErrors.calle && <p className="mt-1 text-sm text-red-600">{dirErrors.calle[0]}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Núm. exterior" />
                                <TextInput
                                    value={dirForm.numero_exterior}
                                    onChange={(e) => setDirForm((p) => ({ ...p, numero_exterior: e.target.value }))}
                                    className="mt-1 block w-full"
                                />
                                {dirErrors.numero_exterior && <p className="mt-1 text-sm text-red-600">{dirErrors.numero_exterior[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Núm. interior" />
                                <TextInput
                                    value={dirForm.numero_interior}
                                    onChange={(e) => setDirForm((p) => ({ ...p, numero_interior: e.target.value }))}
                                    className="mt-1 block w-full"
                                    placeholder="Opcional"
                                />
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Colonia" />
                            <TextInput
                                value={dirForm.colonia}
                                onChange={(e) => setDirForm((p) => ({ ...p, colonia: e.target.value }))}
                                className="mt-1 block w-full"
                            />
                            {dirErrors.colonia && <p className="mt-1 text-sm text-red-600">{dirErrors.colonia[0]}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Estado" />
                                <select
                                    value={dirForm.estado_id}
                                    onChange={(e) => setDirForm((p) => ({ ...p, estado_id: e.target.value, municipio_id: '' }))}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona —</option>
                                    {estados.map((e) => <option key={e.id} value={e.id}>{e.nombre}</option>)}
                                </select>
                                {dirErrors.estado_id && <p className="mt-1 text-sm text-red-600">{dirErrors.estado_id[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Municipio" />
                                <select
                                    value={dirForm.municipio_id}
                                    onChange={(e) => setDirForm((p) => ({ ...p, municipio_id: e.target.value }))}
                                    disabled={!dirForm.estado_id}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona —</option>
                                    {municipiosFiltrados.map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                                </select>
                                {dirErrors.municipio_id && <p className="mt-1 text-sm text-red-600">{dirErrors.municipio_id[0]}</p>}
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Código postal" />
                            <TextInput
                                value={dirForm.codigo_postal}
                                onChange={(e) => setDirForm((p) => ({ ...p, codigo_postal: e.target.value }))}
                                className="mt-1 block w-full"
                                maxLength={10}
                            />
                            {dirErrors.codigo_postal && <p className="mt-1 text-sm text-red-600">{dirErrors.codigo_postal[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowDirModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={dirSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Seleccionar Historias Clínicas ── */}
            {showSelectorHistorias && (
                <Modal title="Seleccionar Historias Clínicas" onClose={() => setShowSelectorHistorias(false)}>
                    <div className="space-y-4">
                        <p className="text-sm text-gray-500">
                            Los conceptos de las historias clínicas que selecciones se agregarán a la nota de pago.
                        </p>
                        <div className="flex gap-2">
                            <TextInput
                                value={busquedaHistorias}
                                onChange={(e) => setBusquedaHistorias(e.target.value)}
                                onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); buscarHistorias(busquedaHistorias); } }}
                                placeholder="Número de caso o nombre del cliente"
                                className="block w-full"
                                autoFocus
                            />
                            <SecondaryButton type="button" onClick={() => buscarHistorias(busquedaHistorias)} disabled={buscandoHistorias}>
                                Buscar
                            </SecondaryButton>
                        </div>

                        <div className="max-h-72 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
                            {buscandoHistorias && <p className="p-2 text-sm text-gray-500">Buscando…</p>}
                            {!buscandoHistorias && resultadosDisponibles.length === 0 && (
                                <p className="p-2 text-sm text-gray-500">Sin resultados.</p>
                            )}
                            {resultadosDisponibles.map((h) => {
                                const marcada = seleccionHistorias.some((s) => s.id === h.id);
                                return (
                                    <label key={h.id} className="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 text-sm hover:bg-gray-50">
                                        <input
                                            type="checkbox"
                                            checked={marcada}
                                            onChange={() => toggleHistoriaSeleccionada(h)}
                                            className="rounded text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="font-medium text-gray-700">Caso {h.numero_caso}</span>
                                        <span className="text-gray-500">{h.propietario}</span>
                                        <span className="ms-auto text-xs text-gray-400">{h.fecha_recepcion}</span>
                                    </label>
                                );
                            })}
                        </div>

                        {seleccionHistorias.length > 0 && (
                            <p className="text-sm text-gray-600">{seleccionHistorias.length} historia(s) seleccionada(s).</p>
                        )}
                        {errorHistorias && <p className="text-sm text-red-600">{errorHistorias}</p>}

                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowSelectorHistorias(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton
                                type="button"
                                onClick={confirmarSelectorHistorias}
                                disabled={aplicandoHistorias || seleccionHistorias.length === 0}
                            >
                                Agregar {seleccionHistorias.length > 0 ? `(${seleccionHistorias.length})` : ''}
                            </PrimaryButton>
                        </div>
                    </div>
                </Modal>
            )}

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    {Object.keys(errors).length > 0 && (
                        <div className="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            Hay errores en el formulario. Revisa los campos marcados antes de guardar.
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        {/* ── Datos del cliente ── */}
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-base font-semibold text-gray-800 border-b border-gray-200 pb-2">
                                Datos del cliente
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <div className="flex items-center justify-between">
                                        <InputLabel value="Nombre" required />
                                        <FieldActions etiqueta="cliente" seleccionado={false} onAdd={openPropModal} onEdit={() => {}} onDelete={() => {}} />
                                    </div>
                                    <select
                                        value={data.propietario_id}
                                        onChange={(e) => onPropietarioChange(e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona un cliente —</option>
                                        {propietarios.map((p) => (
                                            <option key={p.id} value={p.id}>{p.nombre} {p.apellidos}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.propietario_id} className="mt-2" />
                                </div>
                                <div>
                                    <div className="flex items-center justify-between">
                                        <InputLabel value="Dirección" />
                                        <FieldActions
                                            etiqueta="dirección"
                                            disabled={!data.propietario_id}
                                            seleccionado={false}
                                            onAdd={openDirModal}
                                            onEdit={() => {}}
                                            onDelete={() => {}}
                                        />
                                    </div>
                                    <select
                                        value={data.direccion_id}
                                        onChange={(e) => setData('direccion_id', e.target.value)}
                                        disabled={!data.propietario_id}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona una dirección —</option>
                                        {direccionesPropietario.map((d) => (
                                            <option key={d.id} value={d.id}>{formatDireccion(d)}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.direccion_id} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="Teléfono" />
                                    <TextInput value={propietarioSel?.telefono ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="RFC" />
                                    <TextInput value={propietarioSel?.rfc ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="Estado" />
                                    <TextInput value={direccionSel?.estado?.nombre ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="Municipio" />
                                    <TextInput value={direccionSel?.municipio?.nombre ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                            </div>
                        </div>

                        {/* ── Detalles de conceptos ── */}
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <div className="mb-4 flex items-center justify-between border-b border-gray-200 pb-2">
                                <h3 className="text-base font-semibold text-gray-800">Detalles de conceptos</h3>
                                <button
                                    type="button"
                                    onClick={abrirSelectorHistorias}
                                    className="text-sm font-medium text-brand hover:text-brand-hover"
                                >
                                    Seleccionar Historias Clínicas
                                </button>
                            </div>

                            {/* Referencia: historias clínicas que alimentan los conceptos de abajo. */}
                            {historiasSeleccionadas.length > 0 && (
                                <div className="mb-4 flex flex-wrap gap-2">
                                    {historiasSeleccionadas.map((h) => (
                                        <span
                                            key={h.id}
                                            className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 py-1 pl-3 pr-1.5 text-xs font-medium text-indigo-700"
                                        >
                                            Caso {h.numero_caso}
                                            <button
                                                type="button"
                                                onClick={() => quitarHistoriaSeleccionada(h.id)}
                                                className="rounded-full p-0.5 text-indigo-400 hover:bg-indigo-100 hover:text-indigo-700"
                                                title={`Quitar caso ${h.numero_caso}`}
                                            >
                                                ✕
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}
                            <InputError message={errors.historia_clinica_ids} className="mb-4" />

                            {conceptos.length === 0 ? (
                                <div className="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                    Aún no hay conceptos. Usa "Seleccionar Historias Clínicas" para agregarlos automáticamente.
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead>
                                            <tr className="text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                <th className="w-20 py-2 pr-2">Cant.</th>
                                                <th className="py-2 pr-2">Descripción</th>
                                                <th className="w-28 py-2 pr-2">P. Unit.</th>
                                                <th className="w-24 py-2 pr-2">IVA</th>
                                                <th className="w-28 py-2 pr-2">Importe</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {conceptosCalculados.map((concepto, index) => (
                                                <tr key={index}>
                                                    <td className="py-2 pr-2 align-top text-gray-700">{concepto.cantidad}</td>
                                                    <td className="py-2 pr-2 align-top text-gray-700">
                                                        {concepto.descripcion}
                                                        <span className="ml-2 text-xs text-gray-400">(caso {concepto.numero_caso})</span>
                                                        {!concepto.producto_id && (
                                                            <p className="mt-0.5 text-xs text-amber-600">
                                                                ⚠ Sin producto asociado en el catálogo de Pruebas — precio en $0.
                                                            </p>
                                                        )}
                                                    </td>
                                                    <td className="py-2 pr-2 align-top text-gray-700">{dinero(concepto.precio_unitario)}</td>
                                                    <td className="py-2 pr-2 align-top text-gray-600">{dinero(concepto.iva)}</td>
                                                    <td className="py-2 pr-2 align-top font-medium text-gray-700">{dinero(concepto.importe)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <div className="mt-6 flex justify-end">
                                <div className="w-full max-w-xs space-y-1 text-sm">
                                    <div className="flex justify-between text-gray-600">
                                        <span>Sub-Total</span>
                                        <span>{dinero(subtotal)}</span>
                                    </div>
                                    <div className="flex justify-between text-gray-600">
                                        <span>I.V.A. ({Number(ivaPorcentaje)}%)</span>
                                        <span>{dinero(ivaMonto)}</span>
                                    </div>
                                    <div className="flex justify-between border-t border-gray-200 pt-1 text-base font-semibold text-gray-800">
                                        <span>Total</span>
                                        <span>{dinero(total)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ── Pago ── */}
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                                <label className="flex items-center gap-2 text-sm text-gray-700 sm:pb-2">
                                    <input
                                        type="checkbox"
                                        checked={data.pagada}
                                        onChange={(e) => setData('pagada', e.target.checked)}
                                        className="rounded text-indigo-600 focus:ring-indigo-500"
                                    />
                                    Pagado
                                </label>
                                <div>
                                    <InputLabel value="Concepto" />
                                    <select
                                        value={data.concepto}
                                        onChange={(e) => setData('concepto', e.target.value)}
                                        className={selectClass}
                                    >
                                        {conceptosOpciones.map((c) => <option key={c} value={c}>{c}</option>)}
                                    </select>
                                    <InputError message={errors.concepto} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="Tipo de pago" />
                                    <select
                                        value={data.tipo_pago}
                                        onChange={(e) => setData('tipo_pago', e.target.value)}
                                        className={selectClass}
                                    >
                                        {tiposPago.map((t) => <option key={t} value={t}>{t}</option>)}
                                    </select>
                                    <InputError message={errors.tipo_pago} className="mt-2" />
                                </div>
                            </div>

                            <div className="mt-4">
                                <InputLabel value="Notas adicionales" />
                                <textarea
                                    value={data.notas_adicionales}
                                    onChange={(e) => setData('notas_adicionales', e.target.value)}
                                    rows={3}
                                    placeholder="Opcional"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                                <InputError message={errors.notas_adicionales} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>Guardar nota de pago</PrimaryButton>
                            <Link href={route('dashboard')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
