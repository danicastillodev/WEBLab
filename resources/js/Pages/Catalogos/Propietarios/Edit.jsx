import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Edit({ propietario, estados, municipios }) {
    const jalisco = estados.find((e) => e.nombre === 'Jalisco');
    const estadoDefault = jalisco ? String(jalisco.id) : '';

    const direccionVacia = {
        calle: '',
        numero_exterior: '',
        numero_interior: '',
        colonia: '',
        estado_id: estadoDefault,
        municipio_id: '',
        codigo_postal: '',
    };
    const { data, setData, patch, processing, errors } = useForm({
        nombre: propietario.nombre,
        apellidos: propietario.apellidos,
        curp: propietario.curp,
        rfc: propietario.rfc,
        direcciones: propietario.direcciones.map((d) => ({
            calle: d.calle,
            numero_exterior: d.numero_exterior,
            numero_interior: d.numero_interior ?? '',
            colonia: d.colonia,
            estado_id: String(d.estado_id),
            municipio_id: String(d.municipio_id),
            codigo_postal: d.codigo_postal,
        })),
    });

    function submit(e) {
        e.preventDefault();
        patch(route('propietarios.update', propietario.id));
    }

    function updateDireccion(index, field, value) {
        const updated = [...data.direcciones];
        updated[index] = { ...updated[index], [field]: value };
        setData('direcciones', updated);
    }

    function onEstadoChange(index, estadoId) {
        const updated = [...data.direcciones];
        updated[index] = { ...updated[index], estado_id: estadoId, municipio_id: '' };
        setData('direcciones', updated);
    }

    function agregarDireccion() {
        setData('direcciones', [...data.direcciones, { ...direccionVacia }]);
    }

    function quitarDireccion(index) {
        setData('direcciones', data.direcciones.filter((_, i) => i !== index));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Editar propietario
                </h2>
            }
        >
            <Head title="Editar propietario" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">

                            <div>
                                <InputLabel htmlFor="nombre" value="Nombre" />
                                <TextInput
                                    id="nombre"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoFocus
                                />
                                <InputError message={errors.nombre} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="apellidos" value="Apellidos" />
                                <TextInput
                                    id="apellidos"
                                    value={data.apellidos}
                                    onChange={(e) => setData('apellidos', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.apellidos} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="curp" value="CURP" />
                                <TextInput
                                    id="curp"
                                    value={data.curp}
                                    onChange={(e) => setData('curp', e.target.value.toUpperCase())}
                                    className="mt-1 block w-full font-mono uppercase"
                                    maxLength={18}
                                />
                                <InputError message={errors.curp} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="rfc" value="RFC" />
                                <TextInput
                                    id="rfc"
                                    value={data.rfc}
                                    onChange={(e) => setData('rfc', e.target.value.toUpperCase())}
                                    className="mt-1 block w-full font-mono uppercase"
                                    maxLength={13}
                                />
                                <InputError message={errors.rfc} className="mt-2" />
                            </div>

                            {/* Direcciones */}
                            <div>
                                <div className="mb-3 flex items-center justify-between">
                                    <h3 className="text-sm font-medium text-gray-700">Direcciones</h3>
                                    <button
                                        type="button"
                                        onClick={agregarDireccion}
                                        className="text-sm text-indigo-600 hover:text-indigo-900"
                                    >
                                        + Agregar dirección
                                    </button>
                                </div>

                                {errors.direcciones && (
                                    <p className="mb-2 text-sm text-red-600">{errors.direcciones}</p>
                                )}

                                <div className="space-y-4">
                                    {data.direcciones.map((dir, index) => {
                                        const municipiosFiltrados = municipios.filter(
                                            (m) => String(m.estado_id) === String(dir.estado_id),
                                        );

                                        return (
                                            <div key={index} className="rounded-md border border-gray-200 p-4">
                                                <div className="mb-3 flex items-center justify-between">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-gray-400">
                                                        Dirección {index + 1}
                                                    </span>
                                                    {data.direcciones.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => quitarDireccion(index)}
                                                            className="text-xs text-red-500 hover:text-red-700"
                                                        >
                                                            Quitar
                                                        </button>
                                                    )}
                                                </div>

                                                <div className="space-y-3">
                                                    <div>
                                                        <InputLabel value="Calle" />
                                                        <TextInput
                                                            value={dir.calle}
                                                            onChange={(e) => updateDireccion(index, 'calle', e.target.value)}
                                                            className="mt-1 block w-full"
                                                        />
                                                        <InputError message={errors[`direcciones.${index}.calle`]} className="mt-1" />
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <InputLabel value="Número exterior" />
                                                            <TextInput
                                                                value={dir.numero_exterior}
                                                                onChange={(e) => updateDireccion(index, 'numero_exterior', e.target.value)}
                                                                className="mt-1 block w-full"
                                                            />
                                                            <InputError message={errors[`direcciones.${index}.numero_exterior`]} className="mt-1" />
                                                        </div>
                                                        <div>
                                                            <InputLabel value="Número interior" />
                                                            <TextInput
                                                                value={dir.numero_interior}
                                                                onChange={(e) => updateDireccion(index, 'numero_interior', e.target.value)}
                                                                className="mt-1 block w-full"
                                                                placeholder="Opcional"
                                                            />
                                                            <InputError message={errors[`direcciones.${index}.numero_interior`]} className="mt-1" />
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <InputLabel value="Colonia" />
                                                        <TextInput
                                                            value={dir.colonia}
                                                            onChange={(e) => updateDireccion(index, 'colonia', e.target.value)}
                                                            className="mt-1 block w-full"
                                                        />
                                                        <InputError message={errors[`direcciones.${index}.colonia`]} className="mt-1" />
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <InputLabel value="Estado" />
                                                            <select
                                                                value={dir.estado_id}
                                                                onChange={(e) => onEstadoChange(index, e.target.value)}
                                                                className={selectClass}
                                                            >
                                                                <option value="">— Selecciona —</option>
                                                                {estados.map((e) => (
                                                                    <option key={e.id} value={e.id}>{e.nombre}</option>
                                                                ))}
                                                            </select>
                                                            <InputError message={errors[`direcciones.${index}.estado_id`]} className="mt-1" />
                                                        </div>
                                                        <div>
                                                            <InputLabel value="Municipio" />
                                                            <select
                                                                value={dir.municipio_id}
                                                                onChange={(e) => updateDireccion(index, 'municipio_id', e.target.value)}
                                                                disabled={!dir.estado_id}
                                                                className={selectClass}
                                                            >
                                                                <option value="">— Selecciona —</option>
                                                                {municipiosFiltrados.map((m) => (
                                                                    <option key={m.id} value={m.id}>{m.nombre}</option>
                                                                ))}
                                                            </select>
                                                            <InputError message={errors[`direcciones.${index}.municipio_id`]} className="mt-1" />
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <InputLabel value="Código postal" />
                                                        <TextInput
                                                            value={dir.codigo_postal}
                                                            onChange={(e) => updateDireccion(index, 'codigo_postal', e.target.value)}
                                                            className="mt-1 block w-full"
                                                            maxLength={10}
                                                        />
                                                        <InputError message={errors[`direcciones.${index}.codigo_postal`]} className="mt-1" />
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                                <Link
                                    href={route('propietarios.index')}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
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
