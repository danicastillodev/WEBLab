import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ estados }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        estado_id: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('municipios.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nuevo municipio
                </h2>
            }
        >
            <Head title="Nuevo municipio" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="estado_id" value="Estado" />
                                <select
                                    id="estado_id"
                                    value={data.estado_id}
                                    onChange={(e) => setData('estado_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">— Selecciona un estado —</option>
                                    {estados.map((estado) => (
                                        <option key={estado.id} value={estado.id}>
                                            {estado.nombre}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.estado_id} className="mt-2" />
                            </div>

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

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Crear</PrimaryButton>
                                <Link
                                    href={route('municipios.index')}
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
