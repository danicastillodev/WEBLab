import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Create({ servicios }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        servicio_id: '',
        precio_unitario: '',
        activo: true,
    });

    function submit(e) {
        e.preventDefault();
        post(route('productos.store'));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Registrar Nuevo Producto</h2>}
        >
            <Head title="Nuevo producto" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div className="sm:col-span-2">
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
                                    <InputLabel htmlFor="servicio_id" value="Servicio relacionado" />
                                    <select
                                        id="servicio_id"
                                        value={data.servicio_id}
                                        onChange={(e) => setData('servicio_id', e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona un servicio —</option>
                                        {servicios.map((s) => (
                                            <option key={s.id} value={s.id}>{s.descripcion}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.servicio_id} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="precio_unitario" value="Precio Unit." />
                                    <TextInput
                                        id="precio_unitario"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.precio_unitario}
                                        onChange={(e) => setData('precio_unitario', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.precio_unitario} className="mt-2" />
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
                                <PrimaryButton disabled={processing}>Guardar Producto</PrimaryButton>
                                <Link href={route('productos.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
