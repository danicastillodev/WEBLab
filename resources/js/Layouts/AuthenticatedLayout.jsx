import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const { user, permisos } = usePage().props.auth;

    const puedeVer   = (modulo) => !!permisos?.[modulo]?.ver;
    const puedeCrear = (modulo) => !!permisos?.[modulo]?.crear;

    const catalogos = [
        ['propietarios',       'Propietarios',           'propietarios.*'],
        ['municipios',         'Municipios',             'municipios.index'],
        ['estados',            'Estados',                'estados.index'],
        ['especies',           'Especies',               'especies.index'],
        ['razas',              'Razas',                  'razas.index'],
        ['pruebas',            'Pruebas',                'pruebas.index'],
        ['funcion-zootecnicas','Funciones Zootécnicas',  'funcion-zootecnicas.*'],
    ].filter(([modulo]) => puedeVer(modulo));

    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    const [adminOpen, setAdminOpen] = useState(route().current('users.*'));

    const catalogosRoutes = [
        'propietarios.index', 'propietarios.create', 'propietarios.edit',
        'municipios.index', 'estados.index', 'especies.index',
        'razas.index', 'pruebas.index', 'funcion-zootecnicas.index',
    ];
    const [catalogosOpen, setCatalogosOpen] = useState(
        catalogosRoutes.some((r) => route().current(r)),
    );

    const [historiasOpen, setHistoriasOpen] = useState(
        route().current('historias-clinicas.*') || route().current('cuestionario.*'),
    );

    const [facturacionOpen, setFacturacionOpen] = useState(false);

    const [diagnosticosOpen, setDiagnosticosOpen] = useState(false);

    const [serviciosOpen, setServiciosOpen] = useState(false);

    const [reportesOpen, setReportesOpen] = useState(false);

    const [configuracionOpen, setConfiguracionOpen] = useState(
        route().current('areas.*'),
    );

    const chevron = (open) => (
        <svg
            className={`h-4 w-4 transition-transform duration-150 ${open ? 'rotate-180' : ''}`}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
        >
            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
        </svg>
    );

    // Insignia para secciones/enlaces del menú que todavía no tienen una
    // pantalla real detrás (sólo son "href=#" de marcador de posición).
    const insigniaPendiente = (
        <span
            title="Aún sin funcionalidad"
            className="ms-2 inline-flex shrink-0 items-center rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase leading-none tracking-wide text-amber-700"
        >
            Próx.
        </span>
    );

    const groupBtn = (label, open, toggle, active, pendiente = false) => (
        <button
            type="button"
            onClick={toggle}
            className={`flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none ${
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'
            }`}
        >
            <span className="flex items-center">
                {label}
                {pendiente && insigniaPendiente}
            </span>
            {chevron(open)}
        </button>
    );

    const enlacePendiente = (label) => (
        <NavLink
            href="#"
            active={false}
            onClick={(e) => e.preventDefault()}
            className=" !text-amber-700/70 hover:!text-amber-700"
        >
            <span className="flex w-full items-center justify-between">
                {label}
                {insigniaPendiente}
            </span>
        </NavLink>
    );

    const enlacePendienteMovil = (label) => (
        <ResponsiveNavLink
            href="#"
            active={false}
            onClick={(e) => e.preventDefault()}
            className="!text-amber-700/70"
        >
            <span className="flex w-full items-center justify-between">
                {label}
                {insigniaPendiente}
            </span>
        </ResponsiveNavLink>
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
                <nav className="flex-1 space-y-1 overflow-y-auto px-4 py-4">
                    <NavLink href={route('dashboard')} active={route().current('dashboard')}>
                        Panel
                    </NavLink>

                    {/* Historias Clínicas */}
                    {puedeVer('historias-clinicas') && (
                        <div>
                            {groupBtn(
                                'Historias Clínicas',
                                historiasOpen,
                                () => setHistoriasOpen((v) => !v),
                                route().current('historias-clinicas.*') || route().current('cuestionario.*'),
                            )}
                            {historiasOpen && (
                                <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                    <NavLink href={route('historias-clinicas.index')} active={route().current('historias-clinicas.index')}>Listado de historias clínicas</NavLink>
                                    {puedeCrear('historias-clinicas') && (
                                        <NavLink href={route('historias-clinicas.create')} active={route().current('historias-clinicas.create')}>Nueva historia clínica</NavLink>
                                    )}
                                    {puedeVer('cuestionario') && (
                                        <NavLink href={route('cuestionario.index')} active={route().current('cuestionario.*')}>Preguntas del cuestionario</NavLink>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Facturación */}
                    <div>
                        {groupBtn('Facturación', facturacionOpen, () => setFacturacionOpen((v) => !v), false, true)}
                        {facturacionOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                {enlacePendiente('Nueva factura')}
                                {enlacePendiente('Listado facturas')}
                                {enlacePendiente('Nueva nota de abono')}
                                {enlacePendiente('Listado Notas de abono')}
                                {enlacePendiente('Nuevo producto')}
                                {enlacePendiente('Listado productos')}
                                {enlacePendiente('Listado clientes')}
                                {enlacePendiente('Estado de cuenta')}
                            </div>
                        )}
                    </div>

                    {/* Diagnósticos */}
                    <div>
                        {groupBtn('Diagnósticos', diagnosticosOpen, () => setDiagnosticosOpen((v) => !v), false, true)}
                        {diagnosticosOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                {enlacePendiente('Nuevo diagnóstico')}
                                {enlacePendiente('Listado de diagnósticos')}
                            </div>
                        )}
                    </div>

                    {/* Servicios */}
                    <div>
                        {groupBtn('Servicios', serviciosOpen, () => setServiciosOpen((v) => !v), route().current('servicios.*'))}
                        {serviciosOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                {puedeVer('servicios') ? (
                                    <NavLink href={route('servicios.index')} active={route().current('servicios.index') || route().current('servicios.edit')}>Catálogo de servicios</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Catálogo de servicios</NavLink>
                                )}
                                {puedeCrear('servicios') ? (
                                    <NavLink href={route('servicios.create')} active={route().current('servicios.create')}>Agregar servicio</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Agregar servicio</NavLink>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Reportes */}
                    <div>
                        {groupBtn('Reportes', reportesOpen, () => setReportesOpen((v) => !v), false, true)}
                        {reportesOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                {enlacePendiente('Diagnósticos')}
                                {enlacePendiente('Análisis por especie')}
                                {enlacePendiente('Muestras Trabajadas X Especie')}
                                {enlacePendiente('Accesos sospechosos')}
                                {enlacePendiente('Auditoría de Movimientos')}
                                {enlacePendiente('Notificación de res. de B-Agonistas')}
                                {enlacePendiente('Reporte global')}
                                {enlacePendiente('Estado de cuenta por cliente')}
                                {enlacePendiente('Estados Movimientos de facturación')}
                                {enlacePendiente('Volumen de Trabajo')}
                                {enlacePendiente('Muestras / Casos no facturados')}
                            </div>
                        )}
                    </div>

                    {/* Catálogos */}
                    {catalogos.length > 0 && (
                        <div>
                            {groupBtn(
                                'Catálogos',
                                catalogosOpen,
                                () => setCatalogosOpen((v) => !v),
                                catalogosRoutes.some((r) => route().current(r)),
                            )}
                            {catalogosOpen && (
                                <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                    {catalogos.map(([modulo, etiqueta, patron]) => (
                                        <NavLink key={modulo} href={route(`${modulo}.index`)} active={route().current(patron)}>
                                            {etiqueta}
                                        </NavLink>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Configuración General */}
                    <div>
                        {groupBtn('Configuración General', configuracionOpen, () => setConfiguracionOpen((v) => !v), route().current('areas.*') || route().current('datos-generales.*') || route().current('parametros-operacion.*') || route().current('mantenimiento-base-datos.*'))}
                        {configuracionOpen && (
                            <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                {puedeVer('datos-generales') ? (
                                    <NavLink href={route('datos-generales.edit')} active={route().current('datos-generales.*')}>Datos generales</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Datos generales</NavLink>
                                )}
                                {puedeVer('mantenimiento-base-datos') ? (
                                    <NavLink href={route('mantenimiento-base-datos.index')} active={route().current('mantenimiento-base-datos.*')}>Mantenimiento de la base de datos</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Mantenimiento de la base de datos</NavLink>
                                )}
                                {puedeVer('parametros-operacion') ? (
                                    <NavLink href={route('parametros-operacion.edit')} active={route().current('parametros-operacion.*')}>Parámetros de Operación</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Parámetros de Operación</NavLink>
                                )}
                                {puedeVer('areas') ? (
                                    <NavLink href={route('areas.index')} active={route().current('areas.*')}>Catálogo de Áreas</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Catálogo de Áreas</NavLink>
                                )}
                                {puedeVer('servicios') ? (
                                    <NavLink href={route('servicios.index')} active={route().current('servicios.index') || route().current('servicios.edit')}>Catálogo de servicios</NavLink>
                                ) : (
                                    <NavLink href="#" active={false}>Catálogo de servicios</NavLink>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Seguridad */}
                    {puedeVer('users') && (
                        <div>
                            {groupBtn(
                                'Seguridad',
                                adminOpen,
                                () => setAdminOpen((v) => !v),
                                route().current('users.*'),
                            )}
                            {adminOpen && (
                                <div className="ms-3 mt-1 space-y-1 border-l border-gray-200 ps-3">
                                    <NavLink href={route('users.index')} active={route().current('users.*')}>Usuarios</NavLink>
                                </div>
                            )}
                        </div>
                    )}
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
                    <Link href="/"><ApplicationLogo className="h-10 w-auto opacity-90" /></Link>
                    <button
                        onClick={() => setShowingNavigationDropdown((prev) => !prev)}
                        className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                    >
                        <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path className={showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Mobile menu */}
                {showingNavigationDropdown && (
                    <div className="bg-white sm:hidden">
                        <div className="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink href={route('dashboard')} active={route().current('dashboard')}>Panel</ResponsiveNavLink>
                            {puedeVer('historias-clinicas') && (
                                <>
                                    <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Historias Clínicas</div>
                                    <ResponsiveNavLink href={route('historias-clinicas.index')} active={route().current('historias-clinicas.index')}>Listado de historias clínicas</ResponsiveNavLink>
                                    {puedeCrear('historias-clinicas') && (
                                        <ResponsiveNavLink href={route('historias-clinicas.create')} active={route().current('historias-clinicas.create')}>Nueva historia clínica</ResponsiveNavLink>
                                    )}
                                    {puedeVer('cuestionario') && (
                                        <ResponsiveNavLink href={route('cuestionario.index')} active={route().current('cuestionario.*')}>Preguntas del cuestionario</ResponsiveNavLink>
                                    )}
                                </>
                            )}
                            <>
                                <div className="flex items-center px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    Facturación
                                    {insigniaPendiente}
                                </div>
                                {enlacePendienteMovil('Nueva factura')}
                                {enlacePendienteMovil('Listado facturas')}
                                {enlacePendienteMovil('Nueva nota de abono')}
                                {enlacePendienteMovil('Listado Notas de abono')}
                                {enlacePendienteMovil('Nuevo producto')}
                                {enlacePendienteMovil('Listado productos')}
                                {enlacePendienteMovil('Listado clientes')}
                                {enlacePendienteMovil('Estado de cuenta')}
                            </>
                            <>
                                <div className="flex items-center px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    Diagnósticos
                                    {insigniaPendiente}
                                </div>
                                {enlacePendienteMovil('Nuevo diagnóstico')}
                                {enlacePendienteMovil('Listado de diagnósticos')}
                            </>
                            <>
                                <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Servicios</div>
                                {puedeVer('servicios') ? (
                                    <ResponsiveNavLink href={route('servicios.index')} active={route().current('servicios.index') || route().current('servicios.edit')}>Catálogo de servicios</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Catálogo de servicios</ResponsiveNavLink>
                                )}
                                {puedeCrear('servicios') ? (
                                    <ResponsiveNavLink href={route('servicios.create')} active={route().current('servicios.create')}>Agregar servicio</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Agregar servicio</ResponsiveNavLink>
                                )}
                            </>
                            <>
                                <div className="flex items-center px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    Reportes
                                    {insigniaPendiente}
                                </div>
                                {enlacePendienteMovil('Diagnósticos')}
                                {enlacePendienteMovil('Análisis por especie')}
                                {enlacePendienteMovil('Muestras Trabajadas X Especie')}
                                {enlacePendienteMovil('Accesos sospechosos')}
                                {enlacePendienteMovil('Auditoría de Movimientos')}
                                {enlacePendienteMovil('Notificación de res. de B-Agonistas')}
                                {enlacePendienteMovil('Reporte global')}
                                {enlacePendienteMovil('Estado de cuenta por cliente')}
                                {enlacePendienteMovil('Estados Movimientos de facturación')}
                                {enlacePendienteMovil('Volumen de Trabajo')}
                                {enlacePendienteMovil('Muestras / Casos no facturados')}
                            </>
                            {catalogos.length > 0 && (
                                <>
                                    <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Catálogos</div>
                                    {catalogos.map(([modulo, etiqueta, patron]) => (
                                        <ResponsiveNavLink key={modulo} href={route(`${modulo}.index`)} active={route().current(patron)}>
                                            {etiqueta}
                                        </ResponsiveNavLink>
                                    ))}
                                </>
                            )}
                            <>
                                <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Configuración General</div>
                                {puedeVer('datos-generales') ? (
                                    <ResponsiveNavLink href={route('datos-generales.edit')} active={route().current('datos-generales.*')}>Datos generales</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Datos generales</ResponsiveNavLink>
                                )}
                                {puedeVer('mantenimiento-base-datos') ? (
                                    <ResponsiveNavLink href={route('mantenimiento-base-datos.index')} active={route().current('mantenimiento-base-datos.*')}>Mantenimiento de la base de datos</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Mantenimiento de la base de datos</ResponsiveNavLink>
                                )}
                                {puedeVer('parametros-operacion') ? (
                                    <ResponsiveNavLink href={route('parametros-operacion.edit')} active={route().current('parametros-operacion.*')}>Parámetros de Operación</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Parámetros de Operación</ResponsiveNavLink>
                                )}
                                {puedeVer('areas') ? (
                                    <ResponsiveNavLink href={route('areas.index')} active={route().current('areas.*')}>Catálogo de Áreas</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Catálogo de Áreas</ResponsiveNavLink>
                                )}
                                {puedeVer('servicios') ? (
                                    <ResponsiveNavLink href={route('servicios.index')} active={route().current('servicios.index') || route().current('servicios.edit')}>Catálogo de servicios</ResponsiveNavLink>
                                ) : (
                                    <ResponsiveNavLink href="#" active={false}>Catálogo de servicios</ResponsiveNavLink>
                                )}
                            </>
                            {puedeVer('users') && (
                                <>
                                    <div className="px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Seguridad</div>
                                    <ResponsiveNavLink href={route('users.index')} active={route().current('users.*')}>Usuarios</ResponsiveNavLink>
                                </>
                            )}
                        </div>

                        <div className="border-t border-gray-200 pb-1 pt-4">
                            <div className="px-4">
                                <div className="text-base font-medium text-gray-800">{user.name}</div>
                                <div className="text-sm font-medium text-gray-500">{user.email}</div>
                            </div>
                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('profile.edit')}>Perfil</ResponsiveNavLink>
                                <ResponsiveNavLink method="post" href={route('logout')} as="button">Cerrar sesión</ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                )}

                {header && (
                    <header className="bg-white shadow">
                        <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                    </header>
                )}

                <main className="flex-1">{children}</main>
            </div>
        </div>
    );
}
