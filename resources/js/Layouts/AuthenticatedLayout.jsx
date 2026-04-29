import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const [adminOpen, setAdminOpen] = useState(
        route().current('users.*') || route().current('roles.*'),
    );

    const catalogosRoutes = ['propietarios.index', 'propietarios.create', 'propietarios.edit', 'municipios.index', 'estados.index', 'especies.index', 'razas.index', 'pruebas.index'];
    const [catalogosOpen, setCatalogosOpen] = useState(
        catalogosRoutes.some((r) => route().current(r)),
    );

    const [historiasOpen, setHistoriasOpen] = useState(
        route().current('historias-clinicas.*'),
    );

    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* Sidebar */}
            <aside className="hidden w-64 flex-shrink-0 flex-col bg-white shadow-md sm:flex">
                {/* Logo */}
                <div className="flex items-center justify-center border-b border-gray-100 px-6 py-4">
                    <Link href="/">
                        <ApplicationLogo className="h-20 w-auto opacity-90" />
                    </Link>
                </div>

                {/* Nav links */}
                <nav className="flex-1 space-y-1 px-4 py-4">
                    <NavLink
                        href={route('dashboard')}
                        active={route().current('dashboard')}
                    >
                        Panel
                    </NavLink>

                    {/* Historias Clínicas group */}
                    <div>
                        <button
                            type="button"
                            onClick={() => setHistoriasOpen((v) => !v)}
                            className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none ${
                                route().current('historias-clinicas.*')
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'
                            }`}
                        >
                            Historias Clínicas
                            <svg
                                className={`h-4 w-4 transition-transform duration-150 ${historiasOpen ? 'rotate-180' : ''}`}
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </button>

                        {historiasOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                <NavLink href={route('historias-clinicas.index')} active={route().current('historias-clinicas.index')}>Listado</NavLink>
                                <NavLink href={route('historias-clinicas.create')} active={route().current('historias-clinicas.create')}>Nueva</NavLink>
                            </div>
                        )}
                    </div>

                    {/* Catálogos group */}
                    <div>
                        <button
                            type="button"
                            onClick={() => setCatalogosOpen((v) => !v)}
                            className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none ${
                                catalogosRoutes.some((r) => route().current(r))
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'
                            }`}
                        >
                            Catálogos
                            <svg
                                className={`h-4 w-4 transition-transform duration-150 ${catalogosOpen ? 'rotate-180' : ''}`}
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </button>

                        {catalogosOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                <NavLink href={route('propietarios.index')} active={route().current('propietarios.*')}>Propietarios</NavLink>
                                <NavLink href={route('municipios.index')} active={route().current('municipios.index')}>Municipios</NavLink>
                                <NavLink href={route('estados.index')} active={route().current('estados.index')}>Estados</NavLink>
                                <NavLink href={route('especies.index')} active={route().current('especies.index')}>Especies</NavLink>
                                <NavLink href={route('razas.index')} active={route().current('razas.index')}>Razas</NavLink>
                                <NavLink href={route('pruebas.index')} active={route().current('pruebas.index')}>Pruebas</NavLink>
                            </div>
                        )}
                    </div>

                    {/* Administración group */}
                    <div>
                        <button
                            type="button"
                            onClick={() => setAdminOpen((v) => !v)}
                            className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none ${
                                route().current('users.*') || route().current('roles.*')
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'
                            }`}
                        >
                            Administración
                            <svg
                                className={`h-4 w-4 transition-transform duration-150 ${adminOpen ? 'rotate-180' : ''}`}
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </button>

                        {adminOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                <NavLink
                                    href={route('users.index')}
                                    active={route().current('users.*')}
                                >
                                    Usuarios
                                </NavLink>
                                <NavLink
                                    href={route('roles.index')}
                                    active={route().current('roles.*')}
                                >
                                    Roles
                                </NavLink>
                            </div>
                        )}
                    </div>
                </nav>

                {/* User section at bottom */}
                <div className="border-t border-gray-200 px-4 py-4">
                    <div className="mb-2">
                        <div className="text-sm font-medium text-gray-800">{user.name}</div>
                        <div className="text-xs text-gray-500">{user.email}</div>
                    </div>
                    <div className="space-y-1">
                        <NavLink href={route('profile.edit')}>Perfil</NavLink>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="block w-full rounded-md px-3 py-2 text-start text-sm font-medium text-gray-500 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-700"
                        >
                            Cerrar sesión
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex flex-1 flex-col">
                {/* Mobile top bar */}
                <div className="flex items-center justify-between border-b border-gray-100 bg-white px-4 py-3 sm:hidden">
                    <Link href="/">
                        <ApplicationLogo className="h-10 w-auto opacity-90" />
                    </Link>
                    <button
                        onClick={() =>
                            setShowingNavigationDropdown((prev) => !prev)
                        }
                        className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                    >
                        <svg
                            className="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                className={showingNavigationDropdown ? 'inline-flex' : 'hidden'}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                {/* Mobile menu */}
                {showingNavigationDropdown && (
                    <div className="bg-white sm:hidden">
                        <div className="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink
                                href={route('dashboard')}
                                active={route().current('dashboard')}
                            >
                                Panel
                            </ResponsiveNavLink>
                            <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                Historias Clínicas
                            </div>
                            <ResponsiveNavLink href={route('historias-clinicas.index')} active={route().current('historias-clinicas.index')}>Listado</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('historias-clinicas.create')} active={route().current('historias-clinicas.create')}>Nueva</ResponsiveNavLink>
                            <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                Catálogos
                            </div>
                            <ResponsiveNavLink href={route('propietarios.index')} active={route().current('propietarios.*')}>Propietarios</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('municipios.index')} active={route().current('municipios.index')}>Municipios</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('estados.index')} active={route().current('estados.index')}>Estados</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('especies.index')} active={route().current('especies.index')}>Especies</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('razas.index')} active={route().current('razas.index')}>Razas</ResponsiveNavLink>
                            <ResponsiveNavLink href={route('pruebas.index')} active={route().current('pruebas.index')}>Pruebas</ResponsiveNavLink>
                            <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                Administración
                            </div>
                            <ResponsiveNavLink
                                href={route('users.index')}
                                active={route().current('users.*')}
                            >
                                Usuarios
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                href={route('roles.index')}
                                active={route().current('roles.*')}
                            >
                                Roles
                            </ResponsiveNavLink>
                        </div>

                        <div className="border-t border-gray-200 pb-1 pt-4">
                            <div className="px-4">
                                <div className="text-base font-medium text-gray-800">
                                    {user.name}
                                </div>
                                <div className="text-sm font-medium text-gray-500">
                                    {user.email}
                                </div>
                            </div>
                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('profile.edit')}>
                                    Perfil
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                >
                                    Cerrar sesión
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                )}

                {header && (
                    <header className="bg-white shadow">
                        <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}

                <main className="flex-1">{children}</main>
            </div>
        </div>
    );
}
