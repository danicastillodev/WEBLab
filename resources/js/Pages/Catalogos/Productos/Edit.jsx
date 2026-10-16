import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { UNIDADES_PRECIO } from './unidadesPrecio';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export default function Edit({ producto, servicios }) {
    const { data, setData, patch, processing, errors } = useForm({
        nombre: producto.nombre,
        servicio_id: producto.servicio_id ? String(producto.servicio_id) : '',
        precio_unitario: producto.precio_unitario,
        unidad_precio: producto.unidad_precio ?? 'muestra',
        activo: producto.activo,
    });

    function submit(e) {
        e.preventDefault();
        patch(route('productos.update', producto.id));
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Editar producto</h2>}
        >
            <Head title="Editar producto" />

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
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="servicio_id" value="Análisis relacionado" />
                                    <select
                                        id="servicio_id"
                                        value={data.servicio_id}
                                        onChange={(e) => setData('servicio_id', e.target.value)}
                                        className={selectClass}
                                    >
                                        <option value="">— Selecciona un análisis —</option>
                                        {servicios.map((s) => (
                                            <option key={s.id} value={s.id}>{s.descripcion}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.servicio_id} className="mt-2" />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="precio_unitario" value="Precio Unit." />
                                    <div className="mt-1 flex gap-2">
                                        <div className="relative w-1/2">
                                            <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">$</span>
                                            <TextInput
                                                id="precio_unitario"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={data.precio_unitario}
                                                onChange={(e) => setData('precio_unitario', e.target.value)}
                                                className="block w-full pl-7"
                                            />
                                        </div>
                                        <select
                                            id="unidad_precio"
                                            value={data.unidad_precio}
                                            onChange={(e) => setData('unidad_precio', e.target.value)}
                                            className="block w-1/2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            {UNIDADES_PRECIO.map((u) => (
                                                <option key={u.value} value={u.value}>{u.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <InputError message={errors.precio_unitario} className="mt-2" />
                                    <InputError message={errors.unidad_precio} className="mt-2" />
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
                                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                                <Link href={route('productos.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancelar</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
