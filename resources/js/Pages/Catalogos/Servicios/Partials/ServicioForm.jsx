import Checkbox from '@/Components/Checkbox';
import FieldActions from '@/Components/FieldActions';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

const selectClass = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const porNombre = (a, b) => a.nombre.localeCompare(b.nombre);

// Catálogos que se pueden ampliar desde el formulario: clave de la ruta,
// prop con la lista y etiqueta para el modal. Los que tienen `campo` (selección
// única) también se pueden modificar y eliminar.
const CATALOGOS = {
    metodo: { lista: 'metodos', etiqueta: 'método', campo: 'metodo_id' },
    categoria: { lista: 'categorias', etiqueta: 'categoría', campo: 'categoria_id' },
    'tipo-muestra': { lista: 'tiposMuestra', etiqueta: 'tipo de muestra' },
    area: { lista: 'areas', etiqueta: 'área', campo: 'area_id' },
    analista: { lista: 'analistas', etiqueta: 'analista', campo: 'analista_id' },
    norma: { lista: 'normas', etiqueta: 'norma' },
};

function Fila({ etiqueta, htmlFor, error, children, alinearArriba = false }) {
    return (
        <div className="grid grid-cols-1 gap-1 sm:grid-cols-[10rem_1fr] sm:gap-4">
            <InputLabel
                htmlFor={htmlFor}
                value={etiqueta}
                className={`sm:text-right ${alinearArriba ? 'sm:pt-2' : 'sm:pt-3'}`}
            />
            <div>
                {children}
                <InputError message={error} className="mt-1" />
            </div>
        </div>
    );
}

function SelectConAlta({ id, value, onChange, opciones, placeholder, onAdd, onEdit, onDelete, etiqueta }) {
    return (
        <div className="flex items-center gap-2">
            <select id={id} value={value} onChange={(e) => onChange(e.target.value)} className={selectClass}>
                <option value="">{placeholder}</option>
                {opciones.map((o) => (
                    <option key={o.id} value={o.id}>{o.nombre}</option>
                ))}
            </select>
            <FieldActions onAdd={onAdd} onEdit={onEdit} onDelete={onDelete} seleccionado={value !== ''} etiqueta={etiqueta} />
        </div>
    );
}

function ListaConAlta({ opciones, seleccion, onToggle, onAdd, etiqueta, vacio }) {
    return (
        <div className="flex items-start gap-2">
            <div className="h-36 w-full overflow-y-auto rounded-md border border-gray-300 p-2">
                {opciones.length === 0 && <p className="px-1 text-sm text-gray-400">{vacio}</p>}
                {opciones.map((o) => (
                    <label key={o.id} className="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm text-gray-700 hover:bg-gray-50">
                        <Checkbox checked={seleccion.includes(o.id)} onChange={() => onToggle(o.id)} />
                        {o.nombre}
                    </label>
                ))}
            </div>
            <FieldActions onAdd={onAdd} etiqueta={etiqueta} />
        </div>
    );
}

/**
 * Formulario compartido por el alta y la edición de un análisis (Servicio).
 */
export default function ServicioForm({ data, setData, errors, processing, onSubmit, numero, textoGuardar, catalogos: inicial }) {
    const [catalogos, setCatalogos] = useState(inicial);
    const [alta, setAlta] = useState(null); // clave de CATALOGOS que se está dando de alta o modificando
    const [altaAbierta, setAltaAbierta] = useState(false); // separado de `alta` para que el título no se vacíe al cerrar
    const [editandoId, setEditandoId] = useState(null); // null = alta; id = modificación
    const [erroresEliminar, setErroresEliminar] = useState({});
    const [altaNombre, setAltaNombre] = useState('');
    const [altaError, setAltaError] = useState(null);
    const [altaGuardando, setAltaGuardando] = useState(false);

    function abrirAlta(catalogo, registro = null) {
        setAlta(catalogo);
        setEditandoId(registro?.id ?? null);
        setAltaAbierta(true);
        setAltaNombre(registro?.nombre ?? '');
        setAltaError(null);
    }

    function seleccionado(catalogo) {
        const { lista, campo } = CATALOGOS[catalogo];
        return catalogos[lista].find((o) => String(o.id) === String(data[campo]));
    }

    function abrirEdicion(catalogo) {
        abrirAlta(catalogo, seleccionado(catalogo));
    }

    // Accesorios comunes de los selects de catálogo editables.
    function accionesDe(catalogo) {
        const { lista, campo, etiqueta } = CATALOGOS[catalogo];
        return {
            id: campo,
            value: data[campo],
            onChange: (v) => {
                setData(campo, v);
                setErroresEliminar((prev) => ({ ...prev, [catalogo]: null }));
            },
            opciones: catalogos[lista],
            onAdd: () => abrirAlta(catalogo),
            onEdit: () => abrirEdicion(catalogo),
            onDelete: () => eliminar(catalogo),
            etiqueta,
        };
    }

    async function eliminar(catalogo) {
        const registro = seleccionado(catalogo);
        if (!registro) return;
        const { lista, campo, etiqueta } = CATALOGOS[catalogo];
        if (!window.confirm(`¿Eliminar ${etiqueta} "${registro.nombre}"? Esta acción no se puede deshacer.`)) return;

        setErroresEliminar((prev) => ({ ...prev, [catalogo]: null }));
        try {
            await axios.delete(route('servicios.destroy-catalogo', [catalogo, registro.id]));
            setCatalogos((prev) => ({ ...prev, [lista]: prev[lista].filter((o) => o.id !== registro.id) }));
            setData(campo, '');
        } catch (err) {
            const mensaje = err.response?.data?.message ?? 'No se pudo eliminar el registro.';
            setErroresEliminar((prev) => ({ ...prev, [catalogo]: mensaje }));
        }
    }

    function toggle(campo, id) {
        const actual = data[campo];
        setData(campo, actual.includes(id) ? actual.filter((x) => x !== id) : [...actual, id]);
    }

    async function guardarAlta(e) {
        e.preventDefault();
        setAltaGuardando(true);
        setAltaError(null);
        try {
            const { lista, campo } = CATALOGOS[alta];

            if (editandoId) {
                const { data: actualizado } = await axios.patch(route('servicios.update-catalogo', [alta, editandoId]), { nombre: altaNombre });
                setCatalogos((prev) => ({
                    ...prev,
                    [lista]: prev[lista].map((o) => (o.id === actualizado.id ? actualizado : o)).sort(porNombre),
                }));
                setAltaAbierta(false);
                return;
            }

            const { data: nuevo } = await axios.post(route('servicios.store-catalogo', alta), { nombre: altaNombre });
            setCatalogos((prev) => ({ ...prev, [lista]: [...prev[lista], nuevo].sort(porNombre) }));

            // El registro recién creado queda seleccionado.
            if (campo) setData(campo, String(nuevo.id));
            if (alta === 'norma') setData('normas', [...data.normas, nuevo.id]);
            if (alta === 'tipo-muestra') setData('tipos_muestra', [...data.tipos_muestra, nuevo.id]);

            setAltaAbierta(false);
        } catch (err) {
            if (err.response?.status === 422) setAltaError(err.response.data.errors.nombre?.[0]);
            else setAltaError(err.response?.data?.message ?? 'No se pudo guardar el registro.');
        } finally {
            setAltaGuardando(false);
        }
    }

    return (
        <>
            <form onSubmit={onSubmit} className="space-y-4">
                <Fila etiqueta="No.">
                    <TextInput value={numero ?? ''} readOnly disabled className="block w-28 bg-gray-50 text-gray-600" />
                </Fila>

                <Fila etiqueta="Clave" htmlFor="clave" error={errors.clave}>
                    <TextInput
                        id="clave"
                        value={data.clave}
                        onChange={(e) => setData('clave', e.target.value.toUpperCase())}
                        className="block w-40"
                        maxLength={20}
                        autoFocus
                    />
                </Fila>

                <Fila etiqueta="Siglas" htmlFor="siglas" error={errors.siglas}>
                    <TextInput
                        id="siglas"
                        value={data.siglas}
                        onChange={(e) => setData('siglas', e.target.value.toUpperCase())}
                        className="block w-40"
                        maxLength={15}
                    />
                </Fila>

                <Fila etiqueta="Descripción" htmlFor="descripcion" error={errors.descripcion}>
                    <TextInput
                        id="descripcion"
                        value={data.descripcion}
                        onChange={(e) => setData('descripcion', e.target.value)}
                        className="block w-full"
                    />
                </Fila>

                <Fila etiqueta="Nombre del análisis" htmlFor="nombre_analisis" error={errors.nombre_analisis}>
                    <TextInput
                        id="nombre_analisis"
                        value={data.nombre_analisis}
                        onChange={(e) => setData('nombre_analisis', e.target.value)}
                        className="block w-full"
                    />
                </Fila>

                <Fila etiqueta="Método" htmlFor="metodo_id" error={errors.metodo_id ?? erroresEliminar.metodo}>
                    <SelectConAlta {...accionesDe('metodo')} placeholder="— Selecciona un método —" />
                </Fila>

                <Fila etiqueta="Categoría" htmlFor="categoria_id" error={errors.categoria_id ?? erroresEliminar.categoria}>
                    <SelectConAlta {...accionesDe('categoria')} placeholder="— Selecciona una categoría —" />
                </Fila>

                <Fila etiqueta="Tipo de muestra" error={errors.tipos_muestra} alinearArriba>
                    <ListaConAlta
                        opciones={catalogos.tiposMuestra}
                        seleccion={data.tipos_muestra}
                        onToggle={(id) => toggle('tipos_muestra', id)}
                        onAdd={() => abrirAlta('tipo-muestra')}
                        etiqueta="tipo de muestra"
                        vacio="No hay tipos de muestra registrados."
                    />
                </Fila>

                <Fila etiqueta="Área" htmlFor="area_id" error={errors.area_id ?? erroresEliminar.area}>
                    <SelectConAlta {...accionesDe('area')} placeholder="— Selecciona un área —" />
                </Fila>

                <Fila etiqueta="Analista" htmlFor="analista_id" error={errors.analista_id ?? erroresEliminar.analista}>
                    <SelectConAlta {...accionesDe('analista')} placeholder="— Selecciona un analista —" />
                </Fila>

                <Fila etiqueta="Referencias" htmlFor="referencias" error={errors.referencias}>
                    <TextInput
                        id="referencias"
                        value={data.referencias}
                        onChange={(e) => setData('referencias', e.target.value)}
                        className="block w-full"
                    />
                </Fila>

                <Fila etiqueta="Normas" error={errors.normas} alinearArriba>
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <ListaConAlta
                            opciones={catalogos.normas}
                            seleccion={data.normas}
                            onToggle={(id) => toggle('normas', id)}
                            onAdd={() => abrirAlta('norma')}
                            etiqueta="norma"
                            vacio="No hay normas registradas."
                        />
                        <div className="space-y-3 pt-1">
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <Checkbox checked={data.compuesto} onChange={(e) => setData('compuesto', e.target.checked)} />
                                Compuesto por análisis internos
                            </label>
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <Checkbox checked={data.independiente} onChange={(e) => setData('independiente', e.target.checked)} />
                                Impresión independiente del resultado
                            </label>
                        </div>
                    </div>
                </Fila>

                <Fila etiqueta="Status" htmlFor="activo" error={errors.activo}>
                    <select
                        id="activo"
                        value={data.activo ? '1' : '0'}
                        onChange={(e) => setData('activo', e.target.value === '1')}
                        className={selectClass + ' sm:w-48'}
                    >
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </Fila>

                <div className="flex items-center gap-4 pt-2 sm:pl-[11rem]">
                    <PrimaryButton disabled={processing}>{textoGuardar}</PrimaryButton>
                    <Link href={route('servicios.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                </div>
            </form>

            <Modal show={altaAbierta} onClose={() => setAltaAbierta(false)} maxWidth="md">
                <form onSubmit={guardarAlta} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        {editandoId ? 'Modificar' : 'Agregar'} {alta && CATALOGOS[alta].etiqueta}
                    </h2>
                    <div className="mt-4">
                        <InputLabel htmlFor="alta_nombre" value="Nombre" />
                        <TextInput
                            id="alta_nombre"
                            value={altaNombre}
                            onChange={(e) => setAltaNombre(e.target.value)}
                            className="mt-1 block w-full"
                            isFocused
                        />
                        <InputError message={altaError} className="mt-2" />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setAltaAbierta(false)}>Cancelar</SecondaryButton>
                        <PrimaryButton disabled={altaGuardando}>Guardar</PrimaryButton>
                    </div>
                </form>
            </Modal>
        </>
    );
}
