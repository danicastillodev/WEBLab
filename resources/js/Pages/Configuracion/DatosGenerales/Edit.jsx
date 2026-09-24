import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Edit({ datosGenerales, estados, municipios }) {
    const { data, setData, put, processing, errors } = useForm({
        razon_social: datosGenerales?.razon_social ?? '',
        nombre_laboratorio: datosGenerales?.nombre_laboratorio ?? '',
        direccion: datosGenerales?.direccion ?? '',
        colonia: datosGenerales?.colonia ?? '',
        estado_id: datosGenerales?.estado_id ? String(datosGenerales.estado_id) : '',
        municipio_id: datosGenerales?.municipio_id ? String(datosGenerales.municipio_id) : '',
        telefono: datosGenerales?.telefono ?? '',
        email: datosGenerales?.email ?? '',
        jefe_laboratorio: datosGenerales?.jefe_laboratorio ?? '',
    });

    const municipiosFiltrados = municipios.filter(
        (m) => String(m.estado_id) === String(data.estado_id),
    );

    function onEstadoChange(estadoId) {
        setData({ ...data, estado_id: estadoId, municipio_id: '' });
    }

    function submit(e) {
        e.preventDefault();
        put(route('datos-generales.update'));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Datos generales
                </h2>
            }
        >
            <Head title="Datos generales" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="razon_social" value="Razón social" />
                                <TextInput
                                    id="razon_social"
                                    value={data.razon_social}
                                    onChange={(e) => setData('razon_social', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoFocus
                                />
                                <InputError message={errors.razon_social} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="nombre_laboratorio" value="Nombre laboratorio" />
                                <TextInput
                                    id="nombre_laboratorio"
                                    value={data.nombre_laboratorio}
                                    onChange={(e) => setData('nombre_laboratorio', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.nombre_laboratorio} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="direccion" value="Dirección" />
                                <TextInput
                                    id="direccion"
                                    value={data.direccion}
                                    onChange={(e) => setData('direccion', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.direccion} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="colonia" value="Colonia" />
                                <TextInput
                                    id="colonia"
                                    value={data.colonia}
                                    onChange={(e) => setData('colonia', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.colonia} className="mt-2" />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel htmlFor="estado_id" value="Estado" />
                                    <select
                                        id="estado_id"
                                        value={data.estado_id}
                                        onChange={(e) => onEstadoChange(e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona —</option>
                                        {estados.map((estado) => (
                                            <option key={estado.id} value={estado.id}>{estado.nombre}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.estado_id} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="municipio_id" value="Municipio" />
                                    <select
                                        id="municipio_id"
                                        value={data.municipio_id}
                                        onChange={(e) => setData('municipio_id', e.target.value)}
                                        disabled={!data.estado_id}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona —</option>
                                        {municipiosFiltrados.map((municipio) => (
                                            <option key={municipio.id} value={municipio.id}>{municipio.nombre}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.municipio_id} className="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel htmlFor="telefono" value="Teléfono" />
                                <TextInput
                                    id="telefono"
                                    value={data.telefono}
                                    onChange={(e) => setData('telefono', e.target.value.replace(/\D/g, ''))}
                                    className="mt-1 block w-full"
                                    inputMode="numeric"
                                    maxLength={10}
                                    placeholder="Opcional — 10 dígitos"
                                />
                                <InputError message={errors.telefono} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="E-mail" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Opcional"
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="jefe_laboratorio" value="Jefe de laboratorio" />
                                <TextInput
                                    id="jefe_laboratorio"
                                    value={data.jefe_laboratorio}
                                    onChange={(e) => setData('jefe_laboratorio', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.jefe_laboratorio} className="mt-2" />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
