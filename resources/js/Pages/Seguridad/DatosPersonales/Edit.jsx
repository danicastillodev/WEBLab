import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FormErrorAlert from '@/Components/FormErrorAlert';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';

export default function Edit() {
    const { flash, auth } = usePage().props;
    const user = auth.user;

    const datos = useForm({
        name: user.name,
        email: user.email,
    });

    const contrasena = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    function guardarDatos(e) {
        e.preventDefault();
        datos.put(route('datos-personales.update'), { preserveScroll: true });
    }

    function guardarContrasena(e) {
        e.preventDefault();
        contrasena.put(route('datos-personales.update-password'), {
            preserveScroll: true,
            onSuccess: () => contrasena.reset(),
            onError: (errors) => {
                if (errors.password) {
                    contrasena.reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }
                if (errors.current_password) {
                    contrasena.reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Datos personales
                </h2>
            }
        >
            <Head title="Datos personales" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <header>
                            <h3 className="text-lg font-medium text-gray-900">Nombre y correo</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Actualiza el nombre y el correo electrónico de tu cuenta.
                            </p>
                        </header>

                        <form onSubmit={guardarDatos} className="mt-6 space-y-6">
                            <FormErrorAlert errors={datos.errors} />
                            <div>
                                <InputLabel htmlFor="name" value="Nombre" />
                                <TextInput
                                    id="name"
                                    value={datos.data.name}
                                    onChange={(e) => datos.setData('name', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="name"
                                    required
                                    isFocused
                                />
                                <InputError message={datos.errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="Correo electrónico" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    value={datos.data.email}
                                    onChange={(e) => datos.setData('email', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="email"
                                    required
                                />
                                <InputError message={datos.errors.email} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <PrimaryButton disabled={datos.processing}>Guardar datos</PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <header>
                            <h3 className="text-lg font-medium text-gray-900">Contraseña</h3>
                            <p className="mt-1 text-sm text-gray-600">
                                Para cambiarla, confirma primero tu contraseña actual.
                            </p>
                        </header>

                        <form onSubmit={guardarContrasena} className="mt-6 space-y-6">
                            <FormErrorAlert errors={contrasena.errors} serverErrors={false} />
                            <div>
                                <InputLabel htmlFor="current_password" value="Contraseña actual" />
                                <TextInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    type="password"
                                    value={contrasena.data.current_password}
                                    onChange={(e) => contrasena.setData('current_password', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                />
                                <InputError message={contrasena.errors.current_password} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password" value="Nueva contraseña" />
                                <TextInput
                                    id="password"
                                    ref={passwordInput}
                                    type="password"
                                    value={contrasena.data.password}
                                    onChange={(e) => contrasena.setData('password', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                />
                                <InputError message={contrasena.errors.password} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password_confirmation" value="Confirmar nueva contraseña" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    value={contrasena.data.password_confirmation}
                                    onChange={(e) => contrasena.setData('password_confirmation', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                />
                                <InputError message={contrasena.errors.password_confirmation} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <PrimaryButton disabled={contrasena.processing}>Cambiar contraseña</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
