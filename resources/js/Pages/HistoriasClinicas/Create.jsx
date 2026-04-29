import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const EDAD_UNIDADES = ['Dias', 'Meses', 'Años', 'NR', 'NA'];

function formatDireccion(dir) {
    return `${dir.calle} ${dir.numero_exterior}, ${dir.colonia}, ${dir.municipio?.nombre ?? ''}`;
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

export default function Create({ propietarios: initPropietarios, direcciones: initDirecciones, especies, razas, estados, municipios }) {
    const [propietarios, setPropietarios] = useState(initPropietarios);
    const [direcciones, setDirecciones]   = useState(initDirecciones);

    /* ── Main form ── */
    const { data, setData, post, processing, errors } = useForm({
        propietario_id:  '',
        direccion_id:    '',
        fecha_recepcion: '',
        especie_id:      '',
        raza_id:         '',
        edad_unidad:     '',
        edad_valor:      '',
        cantidad:        '',
    });

    const direccionesPropietario = direcciones.filter(
        (d) => String(d.propietario_id) === String(data.propietario_id),
    );
    const edadConValor = data.edad_unidad && !['NR', 'NA'].includes(data.edad_unidad);

    function onPropietarioChange(id) {
        setData((prev) => ({ ...prev, propietario_id: id, direccion_id: '' }));
    }

    function onEdadUnidadChange(val) {
        setData((prev) => ({
            ...prev,
            edad_unidad: val,
            edad_valor: ['NR', 'NA'].includes(val) ? '' : prev.edad_valor,
        }));
    }

    function submit(e) {
        e.preventDefault();
        post(route('historias-clinicas.store'));
    }

    /* ── Propietario modal ── */
    const [showPropModal, setShowPropModal] = useState(false);
    const [propForm, setPropForm]           = useState({ nombre: '', apellidos: '', curp: '', rfc: '' });
    const [propErrors, setPropErrors]       = useState({});
    const [propSaving, setPropSaving]       = useState(false);

    function openPropModal() {
        setPropForm({ nombre: '', apellidos: '', curp: '', rfc: '' });
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
    const jalisco     = estados.find((e) => e.nombre === 'Jalisco');
    const estadoDefault = jalisco ? String(jalisco.id) : '';

    const [showDirModal, setShowDirModal] = useState(false);
    const [dirForm, setDirForm]           = useState({ calle: '', numero_exterior: '', numero_interior: '', colonia: '', estado_id: estadoDefault, municipio_id: '', codigo_postal: '' });
    const [dirErrors, setDirErrors]       = useState({});
    const [dirSaving, setDirSaving]       = useState(false);

    const municipiosFiltrados = municipios.filter(
        (m) => String(m.estado_id) === String(dirForm.estado_id),
    );

    function openDirModal() {
        setDirForm({ calle: '', numero_exterior: '', numero_interior: '', colonia: '', estado_id: estadoDefault, municipio_id: '', codigo_postal: '' });
        setDirErrors({});
        setShowDirModal(true);
    }

    function onDirEstadoChange(val) {
        setDirForm((prev) => ({ ...prev, estado_id: val, municipio_id: '' }));
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
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nueva historia clínica
                </h2>
            }
        >
            <Head title="Nueva historia clínica" />

            {/* ── Propietario modal ── */}
            {showPropModal && (
                <Modal title="Nuevo propietario" onClose={() => setShowPropModal(false)}>
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
                            <InputLabel value="CURP" />
                            <TextInput
                                value={propForm.curp}
                                onChange={(e) => setPropForm((p) => ({ ...p, curp: e.target.value.toUpperCase() }))}
                                className="mt-1 block w-full font-mono uppercase"
                                maxLength={18}
                            />
                            {propErrors.curp && <p className="mt-1 text-sm text-red-600">{propErrors.curp[0]}</p>}
                        </div>
                        <div>
                            <InputLabel value="RFC" />
                            <TextInput
                                value={propForm.rfc}
                                onChange={(e) => setPropForm((p) => ({ ...p, rfc: e.target.value.toUpperCase() }))}
                                className="mt-1 block w-full font-mono uppercase"
                                maxLength={13}
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
                                <select value={dirForm.estado_id} onChange={(e) => onDirEstadoChange(e.target.value)} className={selectClass}>
                                    <option value="">— Selecciona —</option>
                                    {estados.map((e) => <option key={e.id} value={e.id}>{e.nombre}</option>)}
                                </select>
                                {dirErrors.estado_id && <p className="mt-1 text-sm text-red-600">{dirErrors.estado_id[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Municipio" />
                                <select value={dirForm.municipio_id} onChange={(e) => setDirForm((p) => ({ ...p, municipio_id: e.target.value }))} disabled={!dirForm.estado_id} className={selectClass}>
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

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">

                            {/* Propietario */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="propietario_id" value="Propietario" />
                                    <button type="button" onClick={openPropModal} className="text-xs text-indigo-600 hover:text-indigo-900">+ Nuevo propietario</button>
                                </div>
                                <select
                                    id="propietario_id"
                                    value={data.propietario_id}
                                    onChange={(e) => onPropietarioChange(e.target.value)}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona un propietario —</option>
                                    {propietarios.map((p) => (
                                        <option key={p.id} value={p.id}>{p.apellidos}, {p.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.propietario_id} className="mt-2" />
                            </div>

                            {/* Dirección */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="direccion_id" value="Dirección" />
                                    <button
                                        type="button"
                                        onClick={openDirModal}
                                        disabled={!data.propietario_id}
                                        className="text-xs text-indigo-600 hover:text-indigo-900 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        + Nueva dirección
                                    </button>
                                </div>
                                <select
                                    id="direccion_id"
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

                            {/* Fecha de recepción */}
                            <div>
                                <InputLabel htmlFor="fecha_recepcion" value="Fecha de recepción" />
                                <TextInput
                                    id="fecha_recepcion"
                                    type="date"
                                    value={data.fecha_recepcion}
                                    onChange={(e) => setData('fecha_recepcion', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.fecha_recepcion} className="mt-2" />
                            </div>

                            {/* Especie */}
                            <div>
                                <InputLabel htmlFor="especie_id" value="Especie" />
                                <select
                                    id="especie_id"
                                    value={data.especie_id}
                                    onChange={(e) => setData('especie_id', e.target.value)}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona una especie —</option>
                                    {especies.map((e) => (
                                        <option key={e.id} value={e.id}>{e.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.especie_id} className="mt-2" />
                            </div>

                            {/* Raza */}
                            <div>
                                <InputLabel htmlFor="raza_id" value="Raza" />
                                <select
                                    id="raza_id"
                                    value={data.raza_id}
                                    onChange={(e) => setData('raza_id', e.target.value)}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona una raza —</option>
                                    {razas.map((r) => (
                                        <option key={r.id} value={r.id}>{r.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.raza_id} className="mt-2" />
                            </div>

                            {/* Edad */}
                            <div>
                                <InputLabel value="Edad" />
                                <div className="mt-1 flex gap-3">
                                    <select
                                        value={data.edad_unidad}
                                        onChange={(e) => onEdadUnidadChange(e.target.value)}
                                        className="block w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">— Unidad —</option>
                                        {EDAD_UNIDADES.map((u) => (
                                            <option key={u} value={u}>{u}</option>
                                        ))}
                                    </select>
                                    {edadConValor && (
                                        <TextInput
                                            type="number"
                                            min="0"
                                            value={data.edad_valor}
                                            onChange={(e) => setData('edad_valor', e.target.value)}
                                            className="block w-28"
                                            placeholder="Valor"
                                        />
                                    )}
                                </div>
                                <InputError message={errors.edad_unidad} className="mt-2" />
                                <InputError message={errors.edad_valor} className="mt-1" />
                            </div>

                            {/* Cantidad */}
                            <div>
                                <InputLabel htmlFor="cantidad" value="Cantidad" />
                                <TextInput
                                    id="cantidad"
                                    type="number"
                                    min="1"
                                    value={data.cantidad}
                                    onChange={(e) => setData('cantidad', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.cantidad} className="mt-2" />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Crear</PrimaryButton>
                                <Link href={route('historias-clinicas.index')} className="text-sm text-gray-600 hover:text-gray-900">
                                    Cancelar
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
