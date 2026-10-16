import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FormErrorAlert from '@/Components/FormErrorAlert';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SeleccionMultiple from '@/Components/SeleccionMultiple';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ especies }) {
    const { data, setData, post, processing, errors } = useForm({ nombre: '', especie_ids: [] });

    function submit(e) {
        e.preventDefault();
        post(route('funcion-zootecnicas.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Nueva función zootécnica</h2>}
        >
            <Head title="Nueva función zootécnica" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <FormErrorAlert errors={errors} />
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
                                <InputLabel value="Especies" />
                                <p className="mt-1 text-xs text-gray-500">
                                    La función se ofrece al capturar historias clínicas de estas especies.
                                </p>
                                <SeleccionMultiple
                                    opciones={especies}
                                    value={data.especie_ids}
                                    onChange={(ids) => setData('especie_ids', ids)}
                                    placeholder="Buscar especie…"
                                />
                                <InputError message={errors.especie_ids} className="mt-2" />
                            </div>
                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>Crear</PrimaryButton>
                                <Link href={route('funcion-zootecnicas.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
