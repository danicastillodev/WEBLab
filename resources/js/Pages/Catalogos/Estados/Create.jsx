import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        clave: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('estados.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nuevo estado
                </h2>
            }
        >
            <Head title="Nuevo estado" />

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
                                <InputLabel htmlFor="clave" value="Clave" />
                                <TextInput
                                    id="clave"
                                    value={data.clave}
                                    onChange={(e) => setData('clave', e.target.value.toUpperCase())}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.clave} className="mt-2" />
                            </div>

                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Crear</PrimaryButton>
                                <Link
                                    href={route('estados.index')}
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
