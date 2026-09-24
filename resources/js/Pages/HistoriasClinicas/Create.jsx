import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DatePickerInput from '@/Components/DatePickerInput';
import EstadoBadge from '@/Components/EstadoBadge';
import FieldActions from '@/Components/FieldActions';
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

const porNombre = (a, b) => a.nombre.localeCompare(b.nombre);

/** Sustituye un registro editado dentro de su lista local. */
const reemplazar = (lista, item) => lista.map((x) => (x.id === item.id ? item : x));

/** Quita un registro eliminado de su lista local. */
const quitar = (lista, id) => lista.filter((x) => x.id !== id);

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

export default function Create({ propietarios: initPropietarios, direcciones: initDirecciones, explotaciones: initExplotaciones, especies: initEspecies, razas: initRazas, estados, municipios, pruebas: initPruebas, tipos_muestra: initTiposMuestra, funciones_zootecnicas: initFunciones, preguntas = [] }) {
    const [propietarios, setPropietarios]   = useState(initPropietarios);
    const [direcciones, setDirecciones]     = useState(initDirecciones);
    const [explotaciones, setExplotaciones] = useState(initExplotaciones);
    const [especies, setEspecies]           = useState(initEspecies);
    const [razas, setRazas]                 = useState(initRazas);
    const [tiposMuestra, setTiposMuestra]   = useState(initTiposMuestra);
    const [pruebas, setPruebas]             = useState(initPruebas);
    const [funciones, setFunciones]         = useState(initFunciones);

    /* ── Main form ── */
    const { data, setData, post, processing, errors } = useForm({
        propietario_id:  '',
        direccion_id:    '',
        explotacion_id:  '',
        fecha_recepcion: new Date().toLocaleDateString('sv').replace(/-/g, '/'),
        fecha_muestra:   '',
        especie_id:      '',
        raza_id:         '',
        sexo:                   '',
        edad_unidad:            '',
        edad_valor:             '',
        animales_explotacion:   '',
        animales_muertos:       '',
        animales_enfermos:      '',
        funcion_zootecnica_id:  '',
        notas_adicionales:      '',
        muestras:        [{ ...MUESTRA_VACIA }],
        // { [pregunta_id]: respuesta } — se envía junto con la historia.
        respuestas:      {},
    });

    const direccionesPropietario = direcciones.filter(
        (d) => String(d.propietario_id) === String(data.propietario_id),
    );
    const explotacionesPropietario = explotaciones.filter(
        (e) => String(e.propietario_id) === String(data.propietario_id),
    );
    const razasFiltradas = razas.filter(
        (r) => String(r.especie_id) === String(data.especie_id),
    );
    const funcionesFiltradas = funciones.filter(
        (f) => String(f.especie_id) === String(data.especie_id),
    );

    // Registros seleccionados dentro de cada muestra (para editar/eliminar en línea).
    const pruebaPorId = (id) => pruebas.find((p) => String(p.id) === String(id));
    const tipoPorId   = (id) => tiposMuestra.find((t) => String(t.id) === String(id));

    function onPropietarioChange(id) {
        setData((prev) => ({ ...prev, propietario_id: id, direccion_id: '', explotacion_id: '' }));
    }

    function onEspecieChange(id) {
        setData((prev) => ({
            ...prev,
            especie_id: id,
            raza_id: '',
            funcion_zootecnica_id: '',
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

    /* ── Eliminación de registros de catálogo ── */
    const [errorEliminar, setErrorEliminar] = useState('');

    /**
     * Pide confirmación y elimina. El backend responde 409 con el motivo cuando
     * el registro sigue referenciado.
     */
    async function eliminarRegistro(routeName, id, confirmacion, alEliminar) {
        if (!window.confirm(confirmacion)) return;
        setErrorEliminar('');
        try {
            await axios.delete(route(routeName, id));
            alEliminar();
        } catch (err) {
            setErrorEliminar(err.response?.data?.message ?? 'No se pudo eliminar el registro.');
        }
    }

    /* ── Cuestionario modal ── */
    const [showCuestionario, setShowCuestionario] = useState(false);

    const respuestasContestadas = preguntas.filter(
        (p) => String(data.respuestas[p.id] ?? '').trim() !== '',
    ).length;

    function setRespuesta(preguntaId, valor) {
        setData('respuestas', { ...data.respuestas, [preguntaId]: valor });
    }

    /* ── Propietario modal ── */
    const [showPropModal, setShowPropModal] = useState(false);
    const [propEditId, setPropEditId]       = useState(null);
    const [propForm, setPropForm]           = useState({ nombre: '', apellidos: '', curp: '', rfc: '', telefono: '' });
    const [propErrors, setPropErrors]       = useState({});
    const [propSaving, setPropSaving]       = useState(false);

    const propietarioSel = propietarios.find((p) => String(p.id) === String(data.propietario_id));

    function openPropModal(propietario = null) {
        setPropEditId(propietario?.id ?? null);
        setPropForm(propietario
            ? { nombre: propietario.nombre, apellidos: propietario.apellidos, curp: propietario.curp ?? '', rfc: propietario.rfc ?? '', telefono: propietario.telefono ?? '' }
            : { nombre: '', apellidos: '', curp: '', rfc: '', telefono: '' });
        setPropErrors({});
        setShowPropModal(true);
    }

    async function submitPropietario(e) {
        e.preventDefault();
        setPropSaving(true);
        setPropErrors({});
        try {
            if (propEditId) {
                const { data: editado } = await axios.patch(route('historias-clinicas.update-propietario', propEditId), propForm);
                setPropietarios((prev) => reemplazar(prev, editado));
            } else {
                const { data: nuevo } = await axios.post(route('historias-clinicas.store-propietario'), propForm);
                setPropietarios((prev) => [...prev, nuevo].sort((a, b) => a.apellidos.localeCompare(b.apellidos)));
                setData((prev) => ({ ...prev, propietario_id: String(nuevo.id), direccion_id: '' }));
            }
            setShowPropModal(false);
        } catch (err) {
            if (err.response?.status === 422) setPropErrors(err.response.data.errors);
        } finally {
            setPropSaving(false);
        }
    }

    function eliminarPropietario() {
        eliminarRegistro(
            'historias-clinicas.destroy-propietario',
            data.propietario_id,
            `¿Eliminar al propietario "${propietarioSel?.nombre} ${propietarioSel?.apellidos}"? Esta acción no se puede deshacer.`,
            () => {
                const id = Number(data.propietario_id);
                setPropietarios((prev) => quitar(prev, id));
                setDirecciones((prev) => prev.filter((d) => Number(d.propietario_id) !== id));
                setData((prev) => ({ ...prev, propietario_id: '', direccion_id: '' }));
            },
        );
    }

    /* ── Prueba (análisis) modal ── */
    const [showPruebaModal, setShowPruebaModal] = useState(false);
    const [pruebaModalIdx, setPruebaModalIdx]   = useState(0);
    const [pruebaEditId, setPruebaEditId]       = useState(null);
    const [pruebaForm, setPruebaForm]           = useState({ nombre: '' });
    const [pruebaErrors, setPruebaErrors]       = useState({});
    const [pruebaSaving, setPruebaSaving]       = useState(false);

    function openPruebaModal(muestraIndex, prueba = null) {
        setPruebaModalIdx(muestraIndex);
        setPruebaEditId(prueba?.id ?? null);
        setPruebaForm({ nombre: prueba?.nombre ?? '' });
        setPruebaErrors({});
        setShowPruebaModal(true);
    }

    async function submitPrueba(e) {
        e.preventDefault();
        setPruebaSaving(true);
        setPruebaErrors({});
        try {
            if (pruebaEditId) {
                const { data: editada } = await axios.patch(route('historias-clinicas.update-prueba', pruebaEditId), pruebaForm);
                setPruebas((prev) => reemplazar(prev, editada).sort(porNombre));
            } else {
                const payload = { ...pruebaForm, especie_id: data.especie_id };
                const { data: nueva } = await axios.post(route('historias-clinicas.store-prueba'), payload);
                setPruebas((prev) => [...prev, nueva].sort(porNombre));
                updateMuestra(pruebaModalIdx, 'prueba_id', String(nueva.id));
            }
            setShowPruebaModal(false);
        } catch (err) {
            if (err.response?.status === 422) setPruebaErrors(err.response.data.errors);
        } finally {
            setPruebaSaving(false);
        }
    }

    function eliminarPrueba(muestraIndex, prueba) {
        eliminarRegistro(
            'historias-clinicas.destroy-prueba',
            prueba.id,
            `¿Eliminar el análisis "${prueba.nombre}"? También se eliminarán sus tipos de muestra.`,
            () => {
                setPruebas((prev) => quitar(prev, prueba.id));
                setTiposMuestra((prev) => prev.filter((t) => Number(t.prueba_id) !== Number(prueba.id)));
                updateMuestra(muestraIndex, 'prueba_id', '');
            },
        );
    }

    /* ── Especie modal ── */
    const [showEspModal, setShowEspModal] = useState(false);
    const [espEditId, setEspEditId]       = useState(null);
    const [espForm, setEspForm]           = useState({ nombre: '' });
    const [espErrors, setEspErrors]       = useState({});
    const [espSaving, setEspSaving]       = useState(false);

    const especieSel = especies.find((e) => String(e.id) === String(data.especie_id));

    function openEspModal(especie = null) {
        setEspEditId(especie?.id ?? null);
        setEspForm({ nombre: especie?.nombre ?? '' });
        setEspErrors({});
        setShowEspModal(true);
    }

    async function submitEspecie(e) {
        e.preventDefault();
        setEspSaving(true);
        setEspErrors({});
        try {
            if (espEditId) {
                const { data: editada } = await axios.patch(route('historias-clinicas.update-especie', espEditId), espForm);
                setEspecies((prev) => reemplazar(prev, editada).sort(porNombre));
            } else {
                const { data: nueva } = await axios.post(route('historias-clinicas.store-especie'), espForm);
                setEspecies((prev) => [...prev, nueva].sort(porNombre));
                setData((prev) => ({ ...prev, especie_id: String(nueva.id), raza_id: '' }));
            }
            setShowEspModal(false);
        } catch (err) {
            if (err.response?.status === 422) setEspErrors(err.response.data.errors);
        } finally {
            setEspSaving(false);
        }
    }

    function eliminarEspecie() {
        eliminarRegistro(
            'historias-clinicas.destroy-especie',
            data.especie_id,
            `¿Eliminar la especie "${especieSel?.nombre}"? Esta acción no se puede deshacer.`,
            () => {
                setEspecies((prev) => quitar(prev, Number(data.especie_id)));
                onEspecieChange('');
            },
        );
    }

    /* ── Raza modal ── */
    const [showRazaModal, setShowRazaModal] = useState(false);
    const [razaEditId, setRazaEditId]       = useState(null);
    const [razaForm, setRazaForm]           = useState({ nombre: '' });
    const [razaErrors, setRazaErrors]       = useState({});
    const [razaSaving, setRazaSaving]       = useState(false);

    const razaSel = razas.find((r) => String(r.id) === String(data.raza_id));

    function openRazaModal(raza = null) {
        setRazaEditId(raza?.id ?? null);
        setRazaForm({ nombre: raza?.nombre ?? '' });
        setRazaErrors({});
        setShowRazaModal(true);
    }

    async function submitRaza(e) {
        e.preventDefault();
        setRazaSaving(true);
        setRazaErrors({});
        try {
            if (razaEditId) {
                const { data: editada } = await axios.patch(route('historias-clinicas.update-raza', razaEditId), razaForm);
                setRazas((prev) => reemplazar(prev, editada).sort(porNombre));
            } else {
                const payload = { nombre: razaForm.nombre, especie_id: data.especie_id };
                const { data: nueva } = await axios.post(route('historias-clinicas.store-raza'), payload);
                setRazas((prev) => [...prev, nueva].sort(porNombre));
                setData((prev) => ({ ...prev, raza_id: String(nueva.id) }));
            }
            setShowRazaModal(false);
        } catch (err) {
            if (err.response?.status === 422) setRazaErrors(err.response.data.errors);
        } finally {
            setRazaSaving(false);
        }
    }

    function eliminarRaza() {
        eliminarRegistro(
            'historias-clinicas.destroy-raza',
            data.raza_id,
            `¿Eliminar la raza "${razaSel?.nombre}"? Esta acción no se puede deshacer.`,
            () => {
                setRazas((prev) => quitar(prev, Number(data.raza_id)));
                setData((prev) => ({ ...prev, raza_id: '' }));
            },
        );
    }

    /* ── Función zootécnica modal ── */
    const [showFuncModal, setShowFuncModal] = useState(false);
    const [funcEditId, setFuncEditId]       = useState(null);
    const [funcForm, setFuncForm]           = useState({ nombre: '' });
    const [funcErrors, setFuncErrors]       = useState({});
    const [funcSaving, setFuncSaving]       = useState(false);

    const funcionSel = funciones.find((f) => String(f.id) === String(data.funcion_zootecnica_id));

    function openFuncModal(funcion = null) {
        setFuncEditId(funcion?.id ?? null);
        setFuncForm({ nombre: funcion?.nombre ?? '' });
        setFuncErrors({});
        setShowFuncModal(true);
    }

    async function submitFuncion(e) {
        e.preventDefault();
        setFuncSaving(true);
        setFuncErrors({});
        try {
            if (funcEditId) {
                const { data: editada } = await axios.patch(route('historias-clinicas.update-funcion-zootecnica', funcEditId), funcForm);
                setFunciones((prev) => reemplazar(prev, editada).sort(porNombre));
            } else {
                const payload = { ...funcForm, especie_id: data.especie_id };
                const { data: nueva } = await axios.post(route('historias-clinicas.store-funcion-zootecnica'), payload);
                setFunciones((prev) => [...prev, nueva].sort(porNombre));
                setData((prev) => ({ ...prev, funcion_zootecnica_id: String(nueva.id) }));
            }
            setShowFuncModal(false);
        } catch (err) {
            if (err.response?.status === 422) setFuncErrors(err.response.data.errors);
        } finally {
            setFuncSaving(false);
        }
    }

    function eliminarFuncion() {
        eliminarRegistro(
            'historias-clinicas.destroy-funcion-zootecnica',
            data.funcion_zootecnica_id,
            `¿Eliminar la función zootécnica "${funcionSel?.nombre}"? Esta acción no se puede deshacer.`,
            () => {
                setFunciones((prev) => quitar(prev, Number(data.funcion_zootecnica_id)));
                setData((prev) => ({ ...prev, funcion_zootecnica_id: '' }));
            },
        );
    }

    /* ── Tipo de muestra modal ── */
    const [showTipoModal, setShowTipoModal]   = useState(false);
    const [tipoModalCtx, setTipoModalCtx]     = useState({ prueba_id: '', muestraIndex: 0 });
    const [tipoEditId, setTipoEditId]         = useState(null);
    const [tipoForm, setTipoForm]             = useState({ nombre: '' });
    const [tipoErrors, setTipoErrors]         = useState({});
    const [tipoSaving, setTipoSaving]         = useState(false);

    function openTipoModal(muestraIndex, prueba_id, tipo = null) {
        setTipoModalCtx({ prueba_id, muestraIndex });
        setTipoEditId(tipo?.id ?? null);
        setTipoForm({ nombre: tipo?.nombre ?? '' });
        setTipoErrors({});
        setShowTipoModal(true);
    }

    async function submitTipoMuestra(e) {
        e.preventDefault();
        setTipoSaving(true);
        setTipoErrors({});
        try {
            if (tipoEditId) {
                const { data: editado } = await axios.patch(route('historias-clinicas.update-tipo-muestra', tipoEditId), tipoForm);
                setTiposMuestra((prev) => reemplazar(prev, editado).sort(porNombre));
            } else {
                const payload = { nombre: tipoForm.nombre, prueba_id: tipoModalCtx.prueba_id };
                const { data: nuevo } = await axios.post(route('historias-clinicas.store-tipo-muestra'), payload);
                setTiposMuestra((prev) => [...prev, nuevo].sort(porNombre));
                updateMuestra(tipoModalCtx.muestraIndex, 'tipo_muestra_id', String(nuevo.id));
            }
            setShowTipoModal(false);
        } catch (err) {
            if (err.response?.status === 422) setTipoErrors(err.response.data.errors);
        } finally {
            setTipoSaving(false);
        }
    }

    function eliminarTipoMuestra(muestraIndex, tipo) {
        eliminarRegistro(
            'historias-clinicas.destroy-tipo-muestra',
            tipo.id,
            `¿Eliminar el tipo de muestra "${tipo.nombre}"? Esta acción no se puede deshacer.`,
            () => {
                setTiposMuestra((prev) => quitar(prev, tipo.id));
                updateMuestra(muestraIndex, 'tipo_muestra_id', '');
            },
        );
    }

    /* ── Dirección modal ── */
    const jalisco       = estados.find((e) => e.nombre === 'Jalisco');
    const estadoDefault = jalisco ? String(jalisco.id) : '';

    const DIR_VACIA = { calle: '', numero_exterior: '', numero_interior: '', colonia: '', estado_id: estadoDefault, municipio_id: '', codigo_postal: '', caseta: '', lote: '', parvada: '' };

    const [showDirModal, setShowDirModal] = useState(false);
    const [dirEditId, setDirEditId]       = useState(null);
    const [dirForm, setDirForm]           = useState(DIR_VACIA);
    const [dirErrors, setDirErrors]       = useState({});
    const [dirSaving, setDirSaving]       = useState(false);

    const direccionSel = direcciones.find((d) => String(d.id) === String(data.direccion_id));

    const municipiosFiltrados = municipios.filter(
        (m) => String(m.estado_id) === String(dirForm.estado_id),
    );

    function openDirModal(direccion = null) {
        setDirEditId(direccion?.id ?? null);
        setDirForm(direccion
            ? {
                calle:           direccion.calle ?? '',
                numero_exterior: direccion.numero_exterior ?? '',
                numero_interior: direccion.numero_interior ?? '',
                colonia:         direccion.colonia ?? '',
                estado_id:       direccion.municipio?.estado_id ? String(direccion.municipio.estado_id) : estadoDefault,
                municipio_id:    direccion.municipio_id ? String(direccion.municipio_id) : '',
                codigo_postal:   direccion.codigo_postal ?? '',
                caseta:          direccion.caseta ?? '',
                lote:            direccion.lote ?? '',
                parvada:         direccion.parvada ?? '',
            }
            : DIR_VACIA);
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
            if (dirEditId) {
                const { data: editada } = await axios.patch(route('historias-clinicas.update-direccion', dirEditId), dirForm);
                setDirecciones((prev) => reemplazar(prev, editada));
            } else {
                const payload = { ...dirForm, propietario_id: data.propietario_id };
                const { data: nueva } = await axios.post(route('historias-clinicas.store-direccion'), payload);
                setDirecciones((prev) => [...prev, nueva]);
                setData((prev) => ({ ...prev, direccion_id: String(nueva.id) }));
            }
            setShowDirModal(false);
        } catch (err) {
            if (err.response?.status === 422) setDirErrors(err.response.data.errors);
        } finally {
            setDirSaving(false);
        }
    }

    function eliminarDireccion() {
        eliminarRegistro(
            'historias-clinicas.destroy-direccion',
            data.direccion_id,
            `¿Eliminar la dirección "${direccionSel ? formatDireccion(direccionSel) : ''}"? Esta acción no se puede deshacer.`,
            () => {
                setDirecciones((prev) => quitar(prev, Number(data.direccion_id)));
                setData((prev) => ({ ...prev, direccion_id: '' }));
            },
        );
    }

    /* ── Explotación modal ── */
    const EXPL_VACIA = { nombre: '', direccion: '', estado_id: estadoDefault, municipio_id: '', caseta: '', lote: '', parvada: '' };

    const [showExplModal, setShowExplModal] = useState(false);
    const [explEditId, setExplEditId]       = useState(null);
    const [explForm, setExplForm]           = useState(EXPL_VACIA);
    const [explErrors, setExplErrors]       = useState({});
    const [explSaving, setExplSaving]       = useState(false);

    const explotacionSel = explotaciones.find((e) => String(e.id) === String(data.explotacion_id));

    const municipiosFiltradosExpl = municipios.filter(
        (m) => String(m.estado_id) === String(explForm.estado_id),
    );

    function openExplModal(expl = null) {
        setExplEditId(expl?.id ?? null);
        setExplForm(expl
            ? {
                nombre:       expl.nombre ?? '',
                direccion:    expl.direccion ?? '',
                estado_id:    expl.estado_id ? String(expl.estado_id) : estadoDefault,
                municipio_id: expl.municipio_id ? String(expl.municipio_id) : '',
                caseta:       expl.caseta ?? '',
                lote:         expl.lote ?? '',
                parvada:      expl.parvada ?? '',
            }
            : EXPL_VACIA);
        setExplErrors({});
        setShowExplModal(true);
    }

    function onExplEstadoChange(val) {
        setExplForm((prev) => ({ ...prev, estado_id: val, municipio_id: '' }));
    }

    async function submitExplotacion(e) {
        e.preventDefault();
        setExplSaving(true);
        setExplErrors({});
        try {
            if (explEditId) {
                const { data: editada } = await axios.patch(route('historias-clinicas.update-explotacion', explEditId), explForm);
                setExplotaciones((prev) => reemplazar(prev, editada));
            } else {
                const payload = { ...explForm, propietario_id: data.propietario_id };
                const { data: nueva } = await axios.post(route('historias-clinicas.store-explotacion'), payload);
                setExplotaciones((prev) => [...prev, nueva]);
                setData((prev) => ({ ...prev, explotacion_id: String(nueva.id) }));
            }
            setShowExplModal(false);
        } catch (err) {
            if (err.response?.status === 422) setExplErrors(err.response.data.errors);
        } finally {
            setExplSaving(false);
        }
    }

    function eliminarExplotacion() {
        eliminarRegistro(
            'historias-clinicas.destroy-explotacion',
            data.explotacion_id,
            `¿Eliminar la explotación "${explotacionSel?.nombre ?? ''}"? Esta acción no se puede deshacer.`,
            () => {
                setExplotaciones((prev) => quitar(prev, Number(data.explotacion_id)));
                setData((prev) => ({ ...prev, explotacion_id: '' }));
            },
        );
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

            {/* ── Función zootécnica modal ── */}
            {showFuncModal && (
                <Modal title={funcEditId ? 'Editar función zootécnica' : 'Nueva función zootécnica'} onClose={() => setShowFuncModal(false)}>
                    <form onSubmit={submitFuncion} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={funcForm.nombre}
                                onChange={(e) => setFuncForm({ nombre: e.target.value })}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {funcErrors.nombre && <p className="mt-1 text-sm text-red-600">{funcErrors.nombre[0]}</p>}
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowFuncModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={funcSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Tipo de muestra modal ── */}
            {showTipoModal && (
                <Modal title={tipoEditId ? 'Editar tipo de muestra' : 'Nuevo tipo de muestra'} onClose={() => setShowTipoModal(false)}>
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
                <Modal title={pruebaEditId ? 'Editar análisis' : 'Nuevo análisis'} onClose={() => setShowPruebaModal(false)}>
                    <form onSubmit={submitPrueba} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={pruebaForm.nombre}
                                onChange={(e) => setPruebaForm((p) => ({ ...p, nombre: e.target.value }))}
                                className="mt-1 block w-full"
                                autoFocus
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
                <Modal title={espEditId ? 'Editar especie' : 'Nueva especie'} onClose={() => setShowEspModal(false)}>
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
                <Modal title={razaEditId ? 'Editar raza' : 'Nueva raza'} onClose={() => setShowRazaModal(false)}>
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

            {/* ── Cuestionario modal ── */}
            {showCuestionario && (
                <Modal title="Cuestionario" onClose={() => setShowCuestionario(false)}>
                    {preguntas.length === 0 ? (
                        <p className="text-sm text-gray-500">
                            No hay preguntas activas. Se administran en Historias Clínicas → Cuestionario.
                        </p>
                    ) : (
                        <div className="max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                            {preguntas.map((pregunta) => (
                                <div key={pregunta.id}>
                                    <InputLabel value={pregunta.texto} />
                                    {pregunta.tipo === 'si_no' ? (
                                        <select
                                            value={data.respuestas[pregunta.id] ?? ''}
                                            onChange={(e) => setRespuesta(pregunta.id, e.target.value)}
                                            className={selectClass}
                                        >
                                            <option value="">— Sin respuesta —</option>
                                            <option value="Sí">Sí</option>
                                            <option value="No">No</option>
                                        </select>
                                    ) : pregunta.tipo === 'numero' ? (
                                        <TextInput
                                            type="number"
                                            min="0"
                                            step="any"
                                            value={data.respuestas[pregunta.id] ?? ''}
                                            onChange={(e) => setRespuesta(pregunta.id, e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                    ) : (
                                        <textarea
                                            value={data.respuestas[pregunta.id] ?? ''}
                                            onChange={(e) => setRespuesta(pregunta.id, e.target.value)}
                                            rows={2}
                                            maxLength={2000}
                                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    )}
                                    {errors[`respuestas.${pregunta.id}`] && (
                                        <p className="mt-1 text-sm text-red-600">{errors[`respuestas.${pregunta.id}`]}</p>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={() => setShowCuestionario(false)}
                            className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                        >
                            Listo
                        </button>
                    </div>
                </Modal>
            )}

            {/* ── Propietario modal ── */}
            {showPropModal && (
                <Modal title={propEditId ? 'Editar propietario' : 'Nuevo propietario'} onClose={() => setShowPropModal(false)}>
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
                            <InputLabel value="CURP" />
                            <TextInput
                                value={propForm.curp}
                                onChange={(e) => setPropForm((p) => ({ ...p, curp: e.target.value.toUpperCase() }))}
                                className="mt-1 block w-full font-mono uppercase"
                                maxLength={18}
                                placeholder="Opcional"
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
                <Modal title={dirEditId ? 'Editar dirección' : 'Nueva dirección'} onClose={() => setShowDirModal(false)}>
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
                        <div className="grid grid-cols-3 gap-3">
                            <div>
                                <InputLabel value="Caseta" />
                                <TextInput
                                    value={dirForm.caseta}
                                    onChange={(e) => setDirForm((p) => ({ ...p, caseta: e.target.value }))}
                                    className="mt-1 block w-full"
                                    maxLength={50}
                                    placeholder="Opcional"
                                />
                                {dirErrors.caseta && <p className="mt-1 text-sm text-red-600">{dirErrors.caseta[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Lote" />
                                <TextInput
                                    value={dirForm.lote}
                                    onChange={(e) => setDirForm((p) => ({ ...p, lote: e.target.value }))}
                                    className="mt-1 block w-full"
                                    maxLength={50}
                                    placeholder="Opcional"
                                />
                                {dirErrors.lote && <p className="mt-1 text-sm text-red-600">{dirErrors.lote[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Parvada" />
                                <TextInput
                                    value={dirForm.parvada}
                                    onChange={(e) => setDirForm((p) => ({ ...p, parvada: e.target.value }))}
                                    className="mt-1 block w-full"
                                    maxLength={50}
                                    placeholder="Opcional"
                                />
                                {dirErrors.parvada && <p className="mt-1 text-sm text-red-600">{dirErrors.parvada[0]}</p>}
                            </div>
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowDirModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={dirSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ── Modal: Explotación ── */}
            {showExplModal && (
                <Modal title={explEditId ? 'Editar explotación' : 'Nueva explotación'} onClose={() => setShowExplModal(false)}>
                    <form onSubmit={submitExplotacion} className="space-y-4">
                        <div>
                            <InputLabel value="Nombre" />
                            <TextInput
                                value={explForm.nombre}
                                onChange={(e) => setExplForm((p) => ({ ...p, nombre: e.target.value }))}
                                className="mt-1 block w-full"
                                autoFocus
                            />
                            {explErrors.nombre && <p className="mt-1 text-sm text-red-600">{explErrors.nombre[0]}</p>}
                        </div>
                        <div>
                            <InputLabel value="Dirección" />
                            <TextInput
                                value={explForm.direccion}
                                onChange={(e) => setExplForm((p) => ({ ...p, direccion: e.target.value }))}
                                className="mt-1 block w-full"
                                placeholder="Opcional"
                            />
                            {explErrors.direccion && <p className="mt-1 text-sm text-red-600">{explErrors.direccion[0]}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel value="Estado" />
                                <select value={explForm.estado_id} onChange={(e) => onExplEstadoChange(e.target.value)} className={selectClass}>
                                    <option value="">— Selecciona —</option>
                                    {estados.map((e) => <option key={e.id} value={e.id}>{e.nombre}</option>)}
                                </select>
                                {explErrors.estado_id && <p className="mt-1 text-sm text-red-600">{explErrors.estado_id[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Municipio" />
                                <select value={explForm.municipio_id} onChange={(e) => setExplForm((p) => ({ ...p, municipio_id: e.target.value }))} disabled={!explForm.estado_id} className={selectClass}>
                                    <option value="">— Selecciona —</option>
                                    {municipiosFiltradosExpl.map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                                </select>
                                {explErrors.municipio_id && <p className="mt-1 text-sm text-red-600">{explErrors.municipio_id[0]}</p>}
                            </div>
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <div>
                                <InputLabel value="Caseta" />
                                <TextInput
                                    value={explForm.caseta}
                                    onChange={(e) => setExplForm((p) => ({ ...p, caseta: e.target.value }))}
                                    className="mt-1 block w-full"
                                    maxLength={50}
                                    placeholder="Opcional"
                                />
                                {explErrors.caseta && <p className="mt-1 text-sm text-red-600">{explErrors.caseta[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Lote" />
                                <TextInput
                                    value={explForm.lote}
                                    onChange={(e) => setExplForm((p) => ({ ...p, lote: e.target.value }))}
                                    className="mt-1 block w-full"
                                    maxLength={50}
                                    placeholder="Opcional"
                                />
                                {explErrors.lote && <p className="mt-1 text-sm text-red-600">{explErrors.lote[0]}</p>}
                            </div>
                            <div>
                                <InputLabel value="Parvada" />
                                <TextInput
                                    value={explForm.parvada}
                                    onChange={(e) => setExplForm((p) => ({ ...p, parvada: e.target.value }))}
                                    className="mt-1 block w-full"
                                    maxLength={50}
                                    placeholder="Opcional"
                                />
                                {explErrors.parvada && <p className="mt-1 text-sm text-red-600">{explErrors.parvada[0]}</p>}
                            </div>
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button type="button" onClick={() => setShowExplModal(false)} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                            <PrimaryButton disabled={explSaving}>Guardar</PrimaryButton>
                        </div>
                    </form>
                </Modal>
            )}

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            {errorEliminar && (
                                <div className="flex items-start justify-between gap-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                    <span>{errorEliminar}</span>
                                    <button
                                        type="button"
                                        onClick={() => setErrorEliminar('')}
                                        className="text-red-400 hover:text-red-600"
                                    >
                                        ✕
                                    </button>
                                </div>
                            )}

                            {/* Fecha de recepción */}
                            <div>
                                <InputLabel htmlFor="fecha_recepcion" value="Fecha de recepción" />
                                <TextInput
                                    id="fecha_recepcion"
                                    type="text"
                                    value={data.fecha_recepcion}
                                    readOnly
                                    className="mt-1 block w-full bg-gray-100 cursor-not-allowed"
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
                                <InputLabel htmlFor="fecha_muestra" value="Fecha de muestra" required />
                                <DatePickerInput
                                    id="fecha_muestra"
                                    value={data.fecha_muestra}
                                    onChange={(v) => setData('fecha_muestra', v)}
                                    maxDate={new Date()}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.fecha_muestra} className="mt-2" />
                            </div>

                            {/* Especie */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="especie_id" value="Especie" />
                                    <FieldActions
                                        etiqueta="especie"
                                        seleccionado={!!data.especie_id}
                                        onAdd={() => openEspModal()}
                                        onEdit={() => openEspModal(especieSel)}
                                        onDelete={eliminarEspecie}
                                    />
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
                                    <FieldActions
                                        etiqueta="raza"
                                        disabled={!data.especie_id}
                                        seleccionado={!!data.raza_id}
                                        onAdd={() => openRazaModal()}
                                        onEdit={() => openRazaModal(razaSel)}
                                        onDelete={eliminarRaza}
                                    />
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
                                            <span className="text-sm font-medium text-gray-700">Servicio de Análisis {index + 1}</span>
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
                                                <FieldActions
                                                    etiqueta="análisis"
                                                    disabled={!data.especie_id}
                                                    seleccionado={!!muestra.prueba_id}
                                                    onAdd={() => openPruebaModal(index)}
                                                    onEdit={() => openPruebaModal(index, pruebaPorId(muestra.prueba_id))}
                                                    onDelete={() => eliminarPrueba(index, pruebaPorId(muestra.prueba_id))}
                                                />
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
                                                        <option key={p.id} value={p.id}>{p.nombre}</option>
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
                                                    <FieldActions
                                                        etiqueta="tipo de muestra"
                                                        disabled={!muestra.prueba_id}
                                                        seleccionado={!!muestra.tipo_muestra_id}
                                                        onAdd={() => openTipoModal(index, muestra.prueba_id)}
                                                        onEdit={() => openTipoModal(index, muestra.prueba_id, tipoPorId(muestra.tipo_muestra_id))}
                                                        onDelete={() => eliminarTipoMuestra(index, tipoPorId(muestra.tipo_muestra_id))}
                                                    />
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
                                    + Agregar Servicio de análisis
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
                                        <option value="Hembra">Hembra</option>
                                        <option value="Macho">Macho</option>
                                        <option value="Ambos">Ambos</option>
                                        <option value="NA">NA</option>
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

                            {/* Función Zootécnica */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="funcion_zootecnica_id" value="Función Zootécnica" required />
                                    <FieldActions
                                        etiqueta="función zootécnica"
                                        disabled={!data.especie_id}
                                        seleccionado={!!data.funcion_zootecnica_id}
                                        onAdd={() => openFuncModal()}
                                        onEdit={() => openFuncModal(funcionSel)}
                                        onDelete={eliminarFuncion}
                                    />
                                </div>
                                <select
                                    id="funcion_zootecnica_id"
                                    value={data.funcion_zootecnica_id}
                                    onChange={(e) => setData('funcion_zootecnica_id', e.target.value)}
                                    disabled={!data.especie_id}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona —</option>
                                    {funcionesFiltradas.map((f) => (
                                        <option key={f.id} value={f.id}>{f.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.funcion_zootecnica_id} className="mt-2" />
                            </div>

                            {/* ── Datos del propietario ── */}
                            <div>
                                <h3 className="text-base font-semibold text-gray-800 border-b border-gray-200 pb-2">
                                    Datos del propietario
                                </h3>
                            </div>

                            {/* Propietario */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="propietario_id" value="Propietario" />
                                    <FieldActions
                                        etiqueta="propietario"
                                        seleccionado={!!data.propietario_id}
                                        onAdd={() => openPropModal()}
                                        onEdit={() => openPropModal(propietarioSel)}
                                        onDelete={eliminarPropietario}
                                    />
                                </div>
                                <select
                                    id="propietario_id"
                                    value={data.propietario_id}
                                    onChange={(e) => onPropietarioChange(e.target.value)}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona un propietario —</option>
                                    {propietarios.map((p) => (
                                        <option key={p.id} value={p.id}>{p.nombre} {p.apellidos}{p.curp ? ` - ${p.curp}` : ''}</option>
                                    ))}
                                </select>
                                <InputError message={errors.propietario_id} className="mt-2" />
                            </div>

                            {/* Dirección */}
                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="direccion_id" value="Dirección" />
                                    <FieldActions
                                        etiqueta="dirección"
                                        disabled={!data.propietario_id}
                                        seleccionado={!!data.direccion_id}
                                        onAdd={() => openDirModal()}
                                        onEdit={() => openDirModal(direccionSel)}
                                        onDelete={eliminarDireccion}
                                    />
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

                            <div>
                                <div className="flex items-center justify-between">
                                    <InputLabel htmlFor="explotacion_id" value="Explotación" required />
                                    <FieldActions
                                        etiqueta="explotación"
                                        disabled={!data.propietario_id}
                                        seleccionado={!!data.explotacion_id}
                                        onAdd={() => openExplModal()}
                                        onEdit={() => openExplModal(explotacionSel)}
                                        onDelete={eliminarExplotacion}
                                    />
                                </div>
                                <select
                                    id="explotacion_id"
                                    value={data.explotacion_id}
                                    onChange={(e) => setData('explotacion_id', e.target.value)}
                                    disabled={!data.propietario_id}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona una explotación —</option>
                                    {explotacionesPropietario.map((e) => (
                                        <option key={e.id} value={e.id}>{e.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.explotacion_id} className="mt-2" />
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

                            <div>
                                <button
                                    type="button"
                                    onClick={() => setShowCuestionario(true)}
                                    className="rounded-md border border-indigo-200 px-4 py-2 text-sm text-indigo-700 transition hover:bg-indigo-50"
                                >
                                    Cuestionario
                                    {preguntas.length > 0 && (
                                        <span className="ml-2 text-xs text-indigo-500">
                                            {respuestasContestadas}/{preguntas.length}
                                        </span>
                                    )}
                                </button>
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Crear</PrimaryButton>
                                <Link href={route('historias-clinicas.index')} className="text-sm text-gray-600 hover:text-gray-900">
                                    Cancelar
                                </Link>
                                {/* Toda historia nueva nace pendiente; el sistema la mueve después. */}
                                <EstadoBadge estado="pendiente" className="ml-auto" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
