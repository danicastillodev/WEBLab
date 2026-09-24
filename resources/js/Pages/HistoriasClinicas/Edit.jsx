import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EstadoBadge from '@/Components/EstadoBadge';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const EDAD_UNIDADES = ['Dias', 'Meses', 'Años', 'NR', 'NA'];

function formatDireccion(dir) {
    return `${dir.calle} ${dir.numero_exterior}, ${dir.colonia}, ${dir.municipio?.nombre ?? ''}`;
}

export default function Edit({ historia, propietarios, direcciones, especies, razas }) {
    const { data, setData, patch, processing, errors } = useForm({
        propietario_id:  String(historia.propietario_id),
        direccion_id:    String(historia.direccion_id),
        fecha_recepcion: historia.fecha_recepcion?.replace(/-/g, '/') ?? '',
        especie_id:      String(historia.especie_id),
        raza_id:         String(historia.raza_id),
        edad_unidad:     historia.edad_unidad,
        edad_valor:      historia.edad_valor ?? '',
        cantidad:        String(historia.cantidad),
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
        patch(route('historias-clinicas.update', historia.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Editar historia clínica
                </h2>
            }
        >
            <Head title="Editar historia clínica" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">

                            {/* Propietario */}
                            <div>
                                <InputLabel htmlFor="propietario_id" value="Propietario" />
                                <select
                                    id="propietario_id"
                                    value={data.propietario_id}
                                    onChange={(e) => onPropietarioChange(e.target.value)}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona un propietario —</option>
                                    {propietarios.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.nombre} {p.apellidos}{p.curp ? ` - ${p.curp}` : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.propietario_id} className="mt-2" />
                            </div>

                            {/* Dirección */}
                            <div>
                                <InputLabel htmlFor="direccion_id" value="Dirección" />
                                <select
                                    id="direccion_id"
                                    value={data.direccion_id}
                                    onChange={(e) => setData('direccion_id', e.target.value)}
                                    disabled={!data.propietario_id}
                                    className={selectClass}
                                >
                                    <option value="">— Selecciona una dirección —</option>
                                    {direccionesPropietario.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {formatDireccion(d)}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.direccion_id} className="mt-2" />
                            </div>

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
                                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                                <Link
                                    href={route('historias-clinicas.index')}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Cancelar
                                </Link>
                                <EstadoBadge estado={historia.estado} className="ml-auto" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
