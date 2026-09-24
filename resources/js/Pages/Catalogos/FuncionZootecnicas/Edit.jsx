import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Edit({ funcion, especies }) {
    const { data, setData, patch, processing, errors } = useForm({
        especie_id: funcion.especie_id ? String(funcion.especie_id) : '',
        nombre:     funcion.nombre,
    });

    function submit(e) {
        e.preventDefault();
        patch(route('funcion-zootecnicas.update', funcion.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar función zootécnica</h2>}
        >
            <Head title="Editar función zootécnica" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
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
                                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                                <Link href={route('funcion-zootecnicas.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
