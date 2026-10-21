import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center overflow-hidden bg-gray-100 bg-gradient-to-br from-indigo-50 via-sky-50 to-teal-50 pt-6 dark:bg-none sm:justify-center sm:pt-0">
            {/* Manchas de color difuminadas al fondo (sólo tema claro). */}
            <div className="pointer-events-none absolute inset-0 dark:hidden" aria-hidden="true">
                <div className="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-sky-300/30 blur-3xl motion-safe:animate-flotar" />
                <div className="absolute -bottom-32 -left-16 h-[28rem] w-[28rem] rounded-full bg-teal-300/25 blur-3xl motion-safe:animate-flotar-lento" />
            </div>

            <div className="relative motion-safe:animate-aparecer">
                <Link href="/">
                    <ApplicationLogo className="h-48 w-auto opacity-90 drop-shadow-sm" />
                </Link>
            </div>

            <div className="relative mt-6 w-full overflow-hidden bg-white shadow-xl shadow-indigo-500/10 motion-safe:animate-aparecer sm:max-w-md sm:rounded-xl">
                <div className="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-teal-500" aria-hidden="true" />
                <div className="px-6 py-4">{children}</div>
            </div>
        </div>
    );
}
