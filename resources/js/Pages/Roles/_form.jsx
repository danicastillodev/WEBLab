import FormErrorAlert from '@/Components/FormErrorAlert';
import HorarioFields from '@/Components/HorarioFields';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PermisosPorSeccion from '@/Components/PermisosPorSeccion';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

/** Campos compartidos por las pantallas de alta y edición de roles. */
export default function RolForm({
    data,
    setData,
    errors,
    processing,
    submit,
    etiquetaBoton,
    modulos,
    secciones,
    acciones,
    dias,
    gruposPermisos,
}) {
    return (
        <form onSubmit={submit} className="space-y-6">
            <FormErrorAlert errors={errors} />
            <div>
                <InputLabel htmlFor="nombre" value="Nombre" />
                <TextInput
                    id="nombre"
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                    className="mt-1 block w-full"
                    isFocused
                />
                <InputError message={errors.nombre} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="descripcion" value="Descripción" />
                <TextInput
                    id="descripcion"
                    value={data.descripcion}
                    onChange={(e) => setData('descripcion', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.descripcion} className="mt-2" />
            </div>

            <HorarioFields data={data} setData={setData} errors={errors} dias={dias} />

            <PermisosPorSeccion
                data={data}
                setData={setData}
                errors={errors}
                modulos={modulos}
                secciones={secciones}
                acciones={acciones}
                gruposPermisos={gruposPermisos}
            />

            <p className="text-xs text-gray-500">
                El rol funciona como plantilla de horario y permisos: los cambios aplican a
                los usuarios a los que se les asigne a partir de ahora, no a los que ya lo tienen.
            </p>

            <div className="flex items-center gap-4 border-t border-gray-200 pt-6">
                <PrimaryButton disabled={processing}>{etiquetaBoton}</PrimaryButton>
                <Link href={route('roles.index')} className="text-sm text-gray-600 hover:text-gray-900">
                    Cancelar
                </Link>
            </div>
        </form>
    );
}
