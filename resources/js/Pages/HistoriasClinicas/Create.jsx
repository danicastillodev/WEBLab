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
const MUESTRA_VACIA = { prueba_id: '', cantidad: 1, tipo_muestra_id: '', notas: '' };

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

export default function Create({ propietarios: initPropietarios, direcciones: initDirecciones, especies: initEspecies, razas: initRazas, estados, municipios, pruebas: initPruebas, tipos_muestra: initTiposMuestra }) {
    const [propietarios, setPropietarios] = useState(initPropietarios);
    const [direcciones, setDirecciones]   = useState(initDirecciones);
    const [especies, setEspecies]         = useState(initEspecies);
    const [razas, setRazas]               = useState(initRazas);
    const [tiposMuestra, setTiposMuestra] = useState(initTiposMuestra);
    const [pruebas, setPruebas]           = useState(initPruebas);

    /* ── Main form ── */
    const { data, setData, post, processing, errors } = useForm({
        propietario_id:  '',
        direccion_id:    '',
        fecha_recepcion: new Date().toISOString().slice(0, 10),
        fecha_muestra:   '',
        especie_id:      '',
        raza_id:         '',
        sexo:                   '',
        edad_unidad:            '',
        edad_valor:             '',
        animales_explotacion:   '',
        animales_muertos:       '',
        animales_enfermos:      '',
        notas_adicionales:      '',
        muestras:        [{ ...MUESTRA_VACIA }],
    });

    const direccionesPropietario = direcciones.filter(
        (d) => String(d.propietario_id) === String(data.propietario_id),
    );
    const razasFiltradas = razas.filter(
        (r) => String(r.especie_id) === String(data.especie_id),
    );

    function onPropietarioChange(id) {
        setData((prev) => ({ ...prev, propietario_id: id, direccion_id: '' }));
    }

    function onEspecieChange(id) {
        setData((prev) => ({
            ...prev,
            especie_id: id,
            raza_id: '',
            muestras: prev.muestras.map(() => ({ ...MUESTRA_VACIA })),
        }));
    }

    function onEdadUnidadChange(val) {
        setData((prev) => ({
            ...prev,
            edad_unidad: val,
            edad_valor: ['NR', 'NA'].includes(val) ? '' : prev.edad_valor,
        }));
    }

    /* ── Muestras helpers ── */
    function addMuestra() {
        setData('muestras', [...data.muestras, { ...MUESTRA_VACIA }]);
    }

    function removeMuestra(index) {
        setData('muestras', data.muestras.filter((_, i) => i !== index));
    }

    function updateMuestra(index, field, value) {
        const patch = { [field]: value };
        if (field === 'prueba_id') patch.tipo_muestra_id = '';
        const updated = data.muestras.map((m, i) => i === index ? { ...m, ...patch } : m);
        setData('muestras', updated);
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

    /* ── Prueba (análisis) modal ── */
    const [showPruebaModal, setShowPruebaModal] = useState(false);
    const [pruebaModalIdx, setPruebaModalIdx]   = useState(0);
    const [pruebaForm, setPruebaForm]           = useState({ clave: '', nombre: '' });
    const [pruebaErrors, setPruebaErrors]       = useState({});
    const [pruebaSaving, setPruebaSaving]       = useState(false);

    function openPruebaModal(muestraIndex) {
        setPruebaModalIdx(muestraIndex);
        setPruebaForm({ clave: '', nombre: '' });
        setPruebaErrors({});
        setShowPruebaModal(true);
    }

    async function submitPrueba(e) {
        e.preventDefault();
        setPruebaSaving(true);
        setPruebaErrors({});
        try {
            const payload = { ...pruebaForm, especie_id: data.especie_id };
            const { data: nueva } = await axios.post(route('historias-clinicas.store-prueba'), payload);
            setPruebas((prev) => [...prev, nueva].sort((a, b) => a.nombre.localeCompare(b.nombre)));
            updateMuestra(pruebaModalIdx, 'prueba_id', String(nueva.id));
            setShowPruebaModal(false);
        } catch (err) {
            if (err.response?.status === 422) setPruebaErrors(err.response.data.errors);
        } finally {
            setPruebaSaving(false);
        }
    }

    /* ── Especie modal ── */
    const [showEspModal, setShowEspModal] = useState(false);
    const [espForm, setEspForm]           = useState({ nombre: '' });
    const [espErrors, setEspErrors]       = useState({});
    const [espSaving, setEspSaving]       = useState(false);

    function openEspModal() {
        setEspForm({ nombre: '' });
        setEspErrors({});
        setShowEspModal(true);
    }

    async function submitEspecie(e) {
        e.preventDefault();
        setEspSaving(true);
        setEspErrors({});
        try {
            const { data: nueva } = await axios.post(route('historias-clinicas.store-especie'), espForm);
            setEspecies((prev) => [...prev, nueva].sort((a, b) => a.nombre.localeCompare(b.nombre)));
            setData((prev) => ({ ...prev, especie_id: String(nueva.id), raza_id: '' }));
            setShowEspModal(false);
        } catch (err) {
            if (err.response?.status === 422) setEspErrors(err.response.data.errors);
        } finally {
            setEspSaving(false);
        }
    }

    /* ── Raza modal ── */
    const [showRazaModal, setShowRazaModal] = useState(false);
    const [razaForm, setRazaForm]           = useState({ nombre: '' });
    const [razaErrors, setRazaErrors]       = useState({});
    const [razaSaving, setRazaSaving]       = useState(false);

    function openRazaModal() {
        setRazaForm({ nombre: '' });
        setRazaErrors({});
        setShowRazaModal(true);
    }

    async function submitRaza(e) {
        e.preventDefault();
        setRazaSaving(true);
        setRazaErrors({});
        try {
            const payload = { nombre: razaForm.nombre, especie_id: data.especie_id };
            const { data: nueva } = await axios.post(route('historias-clinicas.store-raza'), payload);
            setRazas((prev) => [...prev, nueva].sort((a, b) => a.nombre.localeCompare(b.nombre)));
            setData((prev) => ({ ...prev, raza_id: String(nueva.id) }));
            setShowRazaModal(false);
        } catch (err) {
            if (err.response?.status === 422) setRazaErrors(err.response.data.errors);
        } finally {
            setRazaSaving(false);
        }
    }

    /* ── Tipo de muestra modal ── */
    const [showTipoModal, setShowTipoModal]   = useState(false);
    const [tipoModalCtx, setTipoModalCtx]     = useState({ prueba_id: '', muestraIndex: 0 });
    const [tipoForm, setTipoForm]             = useState({ nombre: '' });
    const [tipoErrors, setTipoErrors]         = useState({});
    const [tipoSaving, setTipoSaving]         = useState(false);

    function openTipoModal(muestraIndex, prueba_id) {
        setTipoModalCtx({ prueba_id, muestraIndex });
        setTipoForm({ nombre: '' });
        setTipoErrors({});
        setShowTipoModal(true);
    }

    async function submitTipoMuestra(e) {
        e.preventDefault();
        setTipoSaving(true);
        setTipoErrors({});
        try {
            const payload = { nombre: tipoForm.nombre, prueba_id: tipoModalCtx.prueba_id };
            const { data: nuevo } = await axios.post(route('historias-clinicas.store-tipo-muestra'), payload);
            setTiposMuestra((prev) => [...prev, nuevo].sort((a, b) => a.nombre.localeCompare(b.nombre)));
            updateMuestra(tipoModalCtx.muestraIndex, 'tipo_muestra_id', String(nuevo.id));
            setShowTipoModal(false);
        } catch (err) {
            if (err.response?.status === 422) setTipoErrors(err.response.data.errors);
        } finally {
            setTipoSaving(false);
        }
    }

    /* ── Dirección modal ── */
    const jalisco       = estados.find((e) => e.nombre === 'Jalisco');
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

            {/* ── Tipo de muestra modal ── */}
            {showTipoModal && (
                <Modal title="Nuevo tipo de muestra" onClose={() => setShowTipoModal(false)}>
                    <form onSubmit={submitTipoMuestra} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={tipoForm.nombre}
                                onChange={(e) => setTipoForm({ nombre: e.target.value })}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {tipoErrors.nombre && <p className="mt-1 text-sm text-red-600">{tipoErrors.nombre[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowTipoModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={tipoSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Prueba modal ── */}
            {showPruebaModal && (
                <Modal title="Nuevo análisis" onClose={() => setShowPruebaModal(false)}>
                    <form onSubmit={submitPrueba} className="space-y-4">
                        <div>
                            <InputLabel value="Clave" />
                            <TextInput
                                value={pruebaForm.clave}
                                onChange={(e) => setPruebaForm((p) => ({ ...p, clave: e.target.value }))}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {pruebaErrors.clave && <p className="mt-1 text-sm text-red-600">{pruebaErrors.clave[0]}</p>}
                        </div>
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={pruebaForm.nombre}
                                onChange={(e) => setPruebaForm((p) => ({ ...p, nombre: e.target.value }))}
                                className="mt-1 block w-full"
                            />
                            {pruebaErrors.nombre && <p className="mt-1 text-sm text-red-600">{pruebaErrors.nombre[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowPruebaModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={pruebaSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Especie modal ── */}
            {showEspModal && (
                <Modal title="Nueva especie" onClose={() => setShowEspModal(false)}>
                    <form onSubmit={submitEspecie} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={espForm.nombre}
                                onChange={(e) => setEspForm({ nombre: e.target.value })}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {espErrors.nombre && <p className="mt-1 text-sm text-red-600">{espErrors.nombre[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowEspModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={espSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Raza modal ── */}
            {showRazaModal && (
                <Modal title="Nueva raza" onClose={() => setShowRazaModal(false)}>
                    <form onSubmit={submitRaza} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={razaForm.nombre}
                                onChange={(e) => setRazaForm({ nombre: e.target.value })}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {razaErrors.nombre && <p className="mt-1 text-sm text-red-600">{razaErrors.nombre[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowRazaModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={razaSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

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

                            {/* ── Detalles de Muestras ── */}
                            <div>
                                <h3 className="text-base font-semibold text-gray-800 border-b border-gray-200 pb-2">
                                    Detalles de Muestras
                                </h3>
                            </div>

                            {/* Fecha de muestra */}
                            <div>
                                <InputLabel htmlFor="fecha_muestra" value="Fecha de muestra" />
                                <TextInput
                                    id="fecha_muestra"
                                    type="date"
                                    value={data.fecha_muestra}
                                    onChange={(e) => setData('fecha_muestra', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.fecha_muestra} className="mt-2" />
                            </div>

                            {/* Especie */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="especie_id" value="Especie" />
                                    <button type="button" onClick={openEspModal} className="text-xs text-indigo-600 hover:text-indigo-900">+ Nueva especie</button>
                                </div>
                                <select
                                    id="especie_id"
                                    value={data.especie_id}
                                    onChange={(e) => onEspecieChange(e.target.value)}
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
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="raza_id" value="Raza" />
                                    <button
                                        type="button"
                                        onClick={openRazaModal}
                                        disabled={!data.especie_id}
                                        className="text-xs text-indigo-600 hover:text-indigo-900 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        + Nueva raza
                                    </button>
                                </div>
                                <select
                                    id="raza_id"
                                    value={data.raza_id}
                                    onChange={(e) => setData('raza_id', e.target.value)}
                                    disabled={!data.especie_id}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona una raza —</option>
                                    {razasFiltradas.map((r) => (
                                        <option key={r.id} value={r.id}>{r.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.raza_id} className="mt-2" />
                            </div>

                            {/* Muestras */}
                            <div className="space-y-3">
                                {data.muestras.map((muestra, index) => (
                                    <div key={index} className="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-gray-700">Muestra {index + 1}</span>
                                            {data.muestras.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => removeMuestra(index)}
                                                    className="text-xs text-red-500 hover:text-red-700"
                                                >
                                                    Eliminar
                                                </button>
                                            )}
                                        </div>

                                        {/* Análisis solicitado */}
                                        <div>
                                            <div className="flex items-center justify-between">
                                                <InputLabel value="Análisis solicitado" />
                                                <button
                                                    type="button"
                                                    onClick={() => openPruebaModal(index)}
                                                    disabled={!data.especie_id}
                                                    className="text-xs text-indigo-600 hover:text-indigo-900 disabled:cursor-not-allowed disabled:opacity-40"
                                                >
                                                    + Nuevo análisis
                                                </button>
                                            </div>
                                            <select
                                                value={muestra.prueba_id}
                                                onChange={(e) => updateMuestra(index, 'prueba_id', e.target.value)}
                                                disabled={!data.especie_id}
                                                className={selectClass}
                                            >
                                                <option value="">— Selecciona un análisis —</option>
                                                {pruebas
                                                    .filter((p) => String(p.especie_id) === String(data.especie_id))
                                                    .map((p) => (
                                                        <option key={p.id} value={p.id}>{p.clave} – {p.nombre}</option>
                                                    ))
                                                }
                                            </select>
                                            {errors[`muestras.${index}.prueba_id`] && (
                                                <p className="mt-1 text-sm text-red-600">{errors[`muestras.${index}.prueba_id`]}</p>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            {/* Cantidad */}
                                            <div>
                                                <InputLabel value="Cantidad" />
                                                <TextInput
                                                    type="number"
                                                    min="1"
                                                    value={muestra.cantidad}
                                                    onChange={(e) => updateMuestra(index, 'cantidad', e.target.value)}
                                                    className="mt-1 block w-full"
                                                />
                                                {errors[`muestras.${index}.cantidad`] && (
                                                    <p className="mt-1 text-sm text-red-600">{errors[`muestras.${index}.cantidad`]}</p>
                                                )}
                                            </div>

                                            {/* Tipo de muestra */}
                                            <div>
                                                <div className="flex items-center justify-between">
                                                    <InputLabel value="Tipo de muestra" />
                                                    <button
                                                        type="button"
                                                        onClick={() => openTipoModal(index, muestra.prueba_id)}
                                                        disabled={!muestra.prueba_id}
                                                        className="text-xs text-indigo-600 hover:text-indigo-900 disabled:cursor-not-allowed disabled:opacity-40"
                                                    >
                                                        + Nuevo tipo
                                                    </button>
                                                </div>
                                                <select
                                                    value={muestra.tipo_muestra_id}
                                                    onChange={(e) => updateMuestra(index, 'tipo_muestra_id', e.target.value)}
                                                    disabled={!muestra.prueba_id}
                                                    className={selectClass}
                                                >
                                                    <option value="">— Selecciona —</option>
                                                    {tiposMuestra
                                                        .filter((t) => String(t.prueba_id) === String(muestra.prueba_id))
                                                        .map((t) => (
                                                            <option key={t.id} value={t.id}>{t.nombre}</option>
                                                        ))
                                                    }
                                                </select>
                                                {errors[`muestras.${index}.tipo_muestra_id`] && (
                                                    <p className="mt-1 text-sm text-red-600">{errors[`muestras.${index}.tipo_muestra_id`]}</p>
                                                )}
                                            </div>
                                        </div>

                                        {/* Notas */}
                                        <div>
                                            <InputLabel value="Notas" />
                                            <textarea
                                                value={muestra.notas}
                                                onChange={(e) => updateMuestra(index, 'notas', e.target.value)}
                                                rows={2}
                                                placeholder="Opcional"
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                            />
                                            {errors[`muestras.${index}.notas`] && (
                                                <p className="mt-1 text-sm text-red-600">{errors[`muestras.${index}.notas`]}</p>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                <button
                                    type="button"
                                    onClick={addMuestra}
                                    className="text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    + Agregar muestra
                                </button>
                                {errors.muestras && (
                                    <p className="mt-1 text-sm text-red-600">{errors.muestras}</p>
                                )}
                            </div>

                            {/* Sexo / Edad / Tiempo */}
                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <InputLabel htmlFor="sexo" value="Sexo" />
                                    <select
                                        id="sexo"
                                        value={data.sexo}
                                        onChange={(e) => setData('sexo', e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona —</option>
                                        <option value="Macho">Macho</option>
                                        <option value="Hembra">Hembra</option>
                                        <option value="Castrado">Castrado</option>
                                        <option value="NR">NR</option>
                                    </select>
                                    <InputError message={errors.sexo} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="edad_valor" value="Edad" />
                                    <TextInput
                                        id="edad_valor"
                                        type="number"
                                        min="0"
                                        value={data.edad_valor}
                                        onChange={(e) => setData('edad_valor', e.target.value)}
                                        disabled={['NR', 'NA'].includes(data.edad_unidad)}
                                        className="mt-1 block w-full disabled:opacity-50"
                                        placeholder="Valor"
                                    />
                                    <InputError message={errors.edad_valor} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="edad_unidad" value="Tiempo" />
                                    <select
                                        id="edad_unidad"
                                        value={data.edad_unidad}
                                        onChange={(e) => onEdadUnidadChange(e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Unidad —</option>
                                        {EDAD_UNIDADES.map((u) => (
                                            <option key={u} value={u}>{u}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.edad_unidad} className="mt-2" />
                                </div>
                            </div>

                            {/* ── Animales ── */}
                            <div>
                                <h3 className="text-base font-semibold text-gray-800 border-b border-gray-200 pb-2">
                                    Animales
                                </h3>
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <InputLabel htmlFor="animales_explotacion" value="En la explotación" />
                                    <TextInput
                                        id="animales_explotacion"
                                        type="number"
                                        min="0"
                                        value={data.animales_explotacion}
                                        onChange={(e) => setData('animales_explotacion', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="—"
                                    />
                                    <InputError message={errors.animales_explotacion} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="animales_muertos" value="Muertos" />
                                    <TextInput
                                        id="animales_muertos"
                                        type="number"
                                        min="0"
                                        value={data.animales_muertos}
                                        onChange={(e) => setData('animales_muertos', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="—"
                                    />
                                    <InputError message={errors.animales_muertos} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="animales_enfermos" value="Enfermos" />
                                    <TextInput
                                        id="animales_enfermos"
                                        type="number"
                                        min="0"
                                        value={data.animales_enfermos}
                                        onChange={(e) => setData('animales_enfermos', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="—"
                                    />
                                    <InputError message={errors.animales_enfermos} className="mt-2" />
                                </div>
                            </div>

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

                            {/* Notas adicionales */}
                            <div>
                                <InputLabel htmlFor="notas_adicionales" value="Notas adicionales" />
                                <textarea
                                    id="notas_adicionales"
                                    value={data.notas_adicionales}
                                    onChange={(e) => setData('notas_adicionales', e.target.value)}
                                    maxLength={512}
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                                <div className="mt-1 text-right text-xs text-gray-400">{data.notas_adicionales.length}/512</div>
                                <InputError message={errors.notas_adicionales} className="mt-1" />
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
