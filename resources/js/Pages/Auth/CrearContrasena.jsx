import FormErrorAlert from '@/Components/FormErrorAlert';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

/**
 * Primer inicio de sesión de un usuario sin contraseña: la crea aquí y queda
 * asignada a su cuenta a partir de ese momento.
 */
export default function CrearContrasena({ nombre, username }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.crear'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Crear contraseña" />

            <h1 className="text-lg font-semibold text-gray-900">Crea tu contraseña</h1>
            <p className="mt-1 text-sm text-gray-600">
                Hola, {nombre}. Tu usuario <span className="font-medium">{username}</span> aún no
                tiene contraseña. Crea una para entrar; la usarás a partir de ahora.
            </p>

            <form onSubmit={submit} className="mt-6">
                <FormErrorAlert errors={errors} className="mb-4" />
                {/* Le dice al navegador para qué cuenta es la contraseña nueva. */}
                <input type="hidden" name="username" value={username} autoComplete="username" />

                <div>
                    <InputLabel htmlFor="password" value="Contraseña nueva" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        isFocused
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <p className="mt-1 text-xs text-gray-500">Mínimo 8 caracteres.</p>
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirmar contraseña" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="mt-6 flex items-center justify-end">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Volver
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Guardar y entrar
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
