import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { METODOS } from './Index';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Create({ areas }) {
    const { data, setData, post, processing, errors } = useForm({
        clave: '',
        siglas: '',
        descripcion: '',
        metodo: '',
        area_id: '',
        referencias: '',
        activo: true,
    });

    function submit(e) {
        e.preventDefault();
        post(route('servicios.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Registrar Nuevo Servicio de Laboratorio</h2>}
        >
            <Head title="Nuevo servicio" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="clave" value="Clave" />
                                    <TextInput
                                        id="clave"
                                        value={data.clave}
                                        onChange={(e) => setData('clave', e.target.value.toUpperCase())}
                                        className="mt-1 block w-full"
                                        placeholder="PCR11"
                                        autoFocus
                                    />
                                    <InputError message={errors.clave} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="siglas" value="Siglas" />
                                    <TextInput
                                        id="siglas"
                                        value={data.siglas}
                                        onChange={(e) => setData('siglas', e.target.value.toUpperCase())}
                                        className="mt-1 block w-full"
                                        placeholder="VAPCR"
                                        maxLength={15}
                                    />
                                    <InputError message={errors.siglas} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="area_id" value="Área" />
                                    <select
                                        id="area_id"
                                        value={data.area_id}
                                        onChange={(e) => setData('area_id', e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona un área —</option>
                                        {areas.map((a) => (
                                            <option key={a.id} value={a.id}>{a.nombre}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.area_id} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="metodo" value="Método" />
                                    <TextInput
                                        id="metodo"
                                        list="metodos-sugeridos"
                                        value={data.metodo}
                                        onChange={(e) => setData('metodo', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="PCR"
                                    />
                                    <datalist id="metodos-sugeridos">
                                        {METODOS.map((m) => <option key={m} value={m} />)}
                                    </datalist>
                                    <InputError message={errors.metodo} className="mt-2" />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="descripcion" value="Descripción" />
                                    <TextInput
                                        id="descripcion"
                                        value={data.descripcion}
                                        onChange={(e) => setData('descripcion', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="DETECCION VIRAL POR PCR"
                                    />
                                    <InputError message={errors.descripcion} className="mt-2" />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="referencias" value="Referencias" />
                                    <TextInput
                                        id="referencias"
                                        value={data.referencias}
                                        onChange={(e) => setData('referencias', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="NOM-005-ZOO-1993"
                                    />
                                    <InputError message={errors.referencias} className="mt-2" />
                                </div>
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="activo"
                                        checked={data.activo}
                                        onChange={(e) => setData('activo', e.target.checked)}
                                    />
                                    <InputLabel htmlFor="activo" value="Activo" className="mb-0" />
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Guardar Servicio</PrimaryButton>
                                <Link href={route('servicios.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
