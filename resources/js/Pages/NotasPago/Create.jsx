import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ComboboxSelect from '@/Components/ComboboxSelect';
import FieldActions from '@/Components/FieldActions';
import FormErrorAlert, { erroresDeAxios } from '@/Components/FormErrorAlert';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';
import { CLIENTE_VACIO, clienteNuevo, formatDomicilio } from '@/Pages/Catalogos/Clientes/Partials/ClienteForm';
import { formatFechaHora } from '@/utils/fechas';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

function dinero(valor) {
    return `$${Number(valor || 0).toFixed(2)}`;
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
    clientes: initClientes,
    estados,
    municipios,
    conceptos_opciones: conceptosOpciones,
    tipos_pago: tiposPago,
    iva_porcentaje: ivaPorcentaje,
    historias_preseleccionadas: historiasPreseleccionadas,
    // Presente sólo al editar: la nota guardada, con sus historias y sus
    // conceptos recalculados (ver NotasPagoController::edit).
    nota = null,
}) {
    const { flash } = usePage().props;
    const [clientes, setClientes] = useState(initClientes);

    const { data, setData, post, put, processing, errors, reset, transform } = useForm({
        cliente_id: nota?.cliente_id ?? '',
        historia_clinica_ids: nota?.historias.map((h) => h.id) ?? [],
        concepto: nota?.concepto ?? conceptosOpciones[0] ?? '',
        tipo_pago: nota?.tipo_pago ?? tiposPago[0] ?? '',
        pagada: nota?.pagada ?? false,
        notas_adicionales: nota?.notas_adicionales ?? '',
    });

    // Los conceptos sólo se agregan automáticamente al seleccionar historias
    // clínicas (ver "Seleccionar Historias Clínicas" más abajo). Sólo la
    // cantidad es editable: el servidor recalcula producto y precio al
    // guardar y aplica las cantidades enviadas en `cantidades`.
    const [conceptos, setConceptos] = useState(nota?.conceptos ?? historiasPreseleccionadas?.conceptos ?? []);
    const [historiasSeleccionadas, setHistoriasSeleccionadas] = useState(nota?.historias ?? historiasPreseleccionadas?.historias ?? []); // [{id, numero_caso}]

    // Al llegar desde "¿Desea crear una nota de pago?" (alta de historia
    // clínica), el servidor ya resolvió qué historias del día de hoy, del
    // usuario actual y sin nota de pago, se preseleccionan: sólo falta
    // reflejarlas en el formulario una vez.
    useEffect(() => {
        if (!historiasPreseleccionadas) return;

        setData((prev) => ({
            ...prev,
            historia_clinica_ids: [...new Set([...prev.historia_clinica_ids, ...historiasPreseleccionadas.historias.map((h) => h.id)])],
        }));
        // Sólo debe aplicarse una vez, al cargar la página con la preselección.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const titulo = nota ? `Editar nota de pago — folio ${nota.folio}` : 'Nueva nota de pago';
    const clienteSel = clientes.find((c) => String(c.id) === String(data.cliente_id));
    const opcionesCliente = useMemo(
        () => clientes.map((c) => ({ value: c.id, label: c.rfc ? `${c.nombre} — ${c.rfc}` : c.nombre })),
        [clientes],
    );

    function quitarHistoriaSeleccionada(historiaId) {
        setConceptos((prev) => prev.filter((c) => c.historia_clinica_id !== historiaId));
        setHistoriasSeleccionadas((prev) => prev.filter((h) => h.id !== historiaId));
        setData('historia_clinica_ids', data.historia_clinica_ids.filter((id) => id !== historiaId));
    }

    function cambiarCantidad(muestraId, valor) {
        setConceptos((prev) => prev.map((c) => (c.muestra_id === muestraId ? { ...c, cantidad: valor } : c)));
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
        transform((datos) => ({
            ...datos,
            cantidades: Object.fromEntries(conceptos.map((c) => [c.muestra_id, c.cantidad])),
        }));
        if (nota) {
            put(route('notas-pago.update', nota.id));
            return;
        }
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
                params: { buscar: texto, nota_pago_id: nota?.id },
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
                params: { historia_clinica_ids: seleccionHistorias.map((h) => h.id), nota_pago_id: nota?.id },
            });

            setConceptos((prev) => [...prev, ...resultado.conceptos]);
            setHistoriasSeleccionadas((prev) => [...prev, ...resultado.historias]);
            setData((prev) => ({
                ...prev,
                historia_clinica_ids: [...new Set([...prev.historia_clinica_ids, ...resultado.historias.map((h) => h.id)])],
            }));
            setShowSelectorHistorias(false);
        } catch (err) {
            setErrorHistorias(err.response?.data?.message ?? 'No se pudieron obtener los conceptos de las historias seleccionadas.');
        } finally {
            setAplicandoHistorias(false);
        }
    }

    /* ── Cliente modal (alta y edición) ── */
    // El cliente es a quien se le emite la nota — distinto al propietario de
    // la historia clínica. Sólo el nombre es obligatorio.
    const [showClienteModal, setShowClienteModal] = useState(false);
    const [clienteEditando, setClienteEditando] = useState(null);
    const [clienteForm, setClienteForm] = useState(() => clienteNuevo(estados));
    const [clienteErrors, setClienteErrors] = useState({});
    const [clienteSaving, setClienteSaving] = useState(false);

    const municipiosFiltrados = municipios.filter((m) => String(m.estado_id) === String(clienteForm.estado_id));

    function openClienteModal(cliente = null) {
        setClienteEditando(cliente);
        setClienteForm(cliente
            ? Object.fromEntries(Object.keys(CLIENTE_VACIO).map((k) => [k, cliente[k] == null ? '' : String(cliente[k])]))
            : clienteNuevo(estados));
        setClienteErrors({});
        setShowClienteModal(true);
    }

    function campoCliente(campo, valor) {
        setClienteForm((prev) => ({ ...prev, [campo]: valor }));
    }

    async function submitCliente(e) {
        e.preventDefault();
        setClienteSaving(true);
        setClienteErrors({});
        try {
            const { data: guardado } = clienteEditando
                ? await axios.patch(route('notas-pago.update-cliente', clienteEditando.id), clienteForm)
                : await axios.post(route('notas-pago.store-cliente'), clienteForm);
            setClientes((prev) => [...prev.filter((c) => c.id !== guardado.id), guardado]
                .sort((a, b) => a.nombre.localeCompare(b.nombre)));
            setData('cliente_id', String(guardado.id));
            setShowClienteModal(false);
        } catch (err) {
            setClienteErrors(erroresDeAxios(err));
        } finally {
            setClienteSaving(false);
        }
    }

    async function eliminarCliente() {
        if (!clienteSel || !window.confirm(`¿Eliminar al cliente ${clienteSel.nombre}?`)) return;
        try {
            await axios.delete(route('notas-pago.destroy-cliente', clienteSel.id));
            setClientes((prev) => prev.filter((c) => c.id !== clienteSel.id));
            setData('cliente_id', '');
        } catch (err) {
            window.alert(err.response?.data?.message ?? 'No se pudo eliminar el cliente.');
        }
    }

    const errorCliente = (campo) => clienteErrors[campo] && (
        <p className="mt-1 text-sm text-red-600">{clienteErrors[campo][0]}</p>
    );

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">{titulo}</h2>}
        >
            <Head title={titulo} />

            {/* ── Cliente modal ── */}
            {showClienteModal && (
                <Modal title={clienteEditando ? 'Editar cliente' : 'Nuevo cliente'} onClose={() => setShowClienteModal(false)}>
                    <form onSubmit={submitCliente} className="space-y-4">
                        <FormErrorAlert errors={clienteErrors} serverErrors={false} />
                        <div>
                            <InputLabel value="Nombre o razón social" required />
                            <TextInput
                                value={clienteForm.nombre}
                                onChange={(e) => campoCliente('nombre', e.target.value)}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {errorCliente('nombre')}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="RFC" />
                                <TextInput
                                    value={clienteForm.rfc}
                                    onChange={(e) => campoCliente('rfc', e.target.value.toUpperCase())}
                                    className="mt-1 block w-full font-mono uppercase"
                                    maxLength={13}
                                />
                                {errorCliente('rfc')}
                            </div>
                            <div>
                                <InputLabel value="Teléfono" />
                                <TextInput
                                    value={clienteForm.telefono}
                                    onChange={(e) => campoCliente('telefono', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1 block w-full"
                                    inputMode="numeric"
                                    maxLength={10}
                                    placeholder="10 dígitos"
                                />
                                {errorCliente('telefono')}
                            </div>
                        </div>
                        <div>
                            <InputLabel value="Calle" />
                            <TextInput
                                value={clienteForm.calle}
                                onChange={(e) => campoCliente('calle', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            {errorCliente('calle')}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Núm. exterior" />
                                <TextInput
                                    value={clienteForm.numero_exterior}
                                    onChange={(e) => campoCliente('numero_exterior', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                {errorCliente('numero_exterior')}
                            </div>
                            <div>
                                <InputLabel value="Núm. interior" />
                                <TextInput
                                    value={clienteForm.numero_interior}
                                    onChange={(e) => campoCliente('numero_interior', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Opcional"
                                />
                                {errorCliente('numero_interior')}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Colonia" />
                                <TextInput
                                    value={clienteForm.colonia}
                                    onChange={(e) => campoCliente('colonia', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                {errorCliente('colonia')}
                            </div>
                            <div>
                                <InputLabel value="Código postal" />
                                <TextInput
                                    value={clienteForm.codigo_postal}
                                    onChange={(e) => campoCliente('codigo_postal', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1 block w-full"
                                    inputMode="numeric"
                                    maxLength={5}
                                />
                                {errorCliente('codigo_postal')}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Estado" />
                                <select
                                    value={clienteForm.estado_id}
                                    onChange={(e) => setClienteForm((p) => ({ ...p, estado_id: e.target.value, municipio_id: '' }))}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona —</option>
                                    {estados.map((e) => <option key={e.id} value={e.id}>{e.nombre}</option>)}
                                </select>
                                {errorCliente('estado_id')}
                            </div>
                            <div>
                                <InputLabel value="Municipio" />
                                <select
                                    value={clienteForm.municipio_id}
                                    onChange={(e) => campoCliente('municipio_id', e.target.value)}
                                    disabled={!clienteForm.estado_id}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona —</option>
                                    {municipiosFiltrados.map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                                </select>
                                {errorCliente('municipio_id')}
                            </div>
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowClienteModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={clienteSaving}>Guardar</PrimaryButton>
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
                                placeholder="Número de caso o nombre del propietario"
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
                                        {h.facturada_parcialmente && (
                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Facturada parcialmente</span>
                                        )}
                                        <span className="text-gray-500">{h.propietario}</span>
                                        <span className="ms-auto text-xs text-gray-400">{formatFechaHora(h.fecha_recepcion)}</span>
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

                    <form onSubmit={submit} className="space-y-6">
                        <FormErrorAlert errors={errors} />
                        {/* ── Datos del cliente ── */}
                        <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                            <h3 className="mb-4 text-base font-semibold text-gray-800 border-b border-gray-200 pb-2">
                                Datos del cliente
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <div className="flex items-center justify-between">
                                        <InputLabel value="Cliente" required />
                                        <FieldActions
                                            etiqueta="cliente"
                                            seleccionado={!!clienteSel}
                                            onAdd={() => openClienteModal()}
                                            onEdit={() => openClienteModal(clienteSel)}
                                            onDelete={eliminarCliente}
                                        />
                                    </div>
                                    <ComboboxSelect
                                        id="cliente_id"
                                        value={data.cliente_id}
                                        onChange={(id) => setData('cliente_id', id)}
                                        options={opcionesCliente}
                                        placeholder="Escribe para buscar un cliente (nombre o RFC)"
                                    />
                                    <InputError message={errors.cliente_id} className="mt-2" />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel value="Dirección" />
                                    <TextInput value={formatDomicilio(clienteSel)} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="Teléfono" />
                                    <TextInput value={clienteSel?.telefono ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="RFC" />
                                    <TextInput value={clienteSel?.rfc ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="Estado" />
                                    <TextInput value={clienteSel?.estado?.nombre ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
                                </div>
                                <div>
                                    <InputLabel value="Municipio" />
                                    <TextInput value={clienteSel?.municipio?.nombre ?? ''} readOnly className="mt-1 block w-full bg-gray-100" />
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
                                                <tr key={concepto.muestra_id ?? index}>
                                                    <td className="py-2 pr-2 align-top">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            step="1"
                                                            value={concepto.cantidad}
                                                            onChange={(e) => cambiarCantidad(concepto.muestra_id, e.target.value)}
                                                            className="w-16 rounded-md border border-gray-300 px-2 py-1 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                                                        />
                                                        {concepto.por_facturar !== undefined && (
                                                            <p className="mt-0.5 whitespace-nowrap text-xs text-gray-400">
                                                                Por facturar: {concepto.por_facturar} de {concepto.cantidad_muestra}
                                                            </p>
                                                        )}
                                                        {errors[`cantidades.${concepto.muestra_id}`] && (
                                                            <p className="mt-0.5 text-xs text-red-600">{errors[`cantidades.${concepto.muestra_id}`]}</p>
                                                        )}
                                                    </td>
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
                            <PrimaryButton disabled={processing}>{nota ? 'Guardar cambios' : 'Guardar nota de pago'}</PrimaryButton>
                            <Link href={route(nota ? 'notas-pago.index' : 'dashboard')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
