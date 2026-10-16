import ApplicationLogo from '@/Components/ApplicationLogo';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const { user, permisos, permisosGranulares } = usePage().props.auth;

    const puedeVer   = (modulo) => !!permisos?.[modulo]?.ver;
    const puedeCrear = (modulo) => !!permisos?.[modulo]?.crear;
    const puedeGranular = (clave) => !!permisosGranulares?.[clave];
    const puedeAlguno = (...claves) => claves.some(puedeGranular);
    const actual = (...patrones) => patrones.some((p) => route().current(p));

    // Enlace con pantalla real: { etiqueta, href, activo, visible }.
    // Enlace pendiente (sin pantalla aún): { etiqueta, pendiente: true, visible }.
    const enlace = (etiqueta, nombreRuta, activo, visible) => ({
        etiqueta,
        href: visible ? route(nombreRuta) : '#',
        activo,
        visible,
    });
    const pendiente = (etiqueta, visible) => ({ etiqueta, pendiente: true, visible });

    // Menú lateral. Cada enlace sólo se muestra si el usuario tiene permiso
    // para él, y un grupo sin ningún enlace visible se oculta por completo.
    const grupos = [
        {
            clave: 'configuracion',
            etiqueta: 'Configuración General',
            activo: actual('areas.*', 'datos-generales.*', 'parametros-operacion.*', 'mantenimiento-base-datos.*', 'servicios.*'),
            enlaces: [
                enlace('Datos generales', 'datos-generales.edit', actual('datos-generales.*'), puedeVer('datos-generales')),
                enlace('Mantenimiento de la base de datos', 'mantenimiento-base-datos.index', actual('mantenimiento-base-datos.*'), puedeVer('mantenimiento-base-datos')),
                enlace('Parámetros de Operación', 'parametros-operacion.edit', actual('parametros-operacion.*'), puedeVer('parametros-operacion')),
                enlace('Catálogo de Áreas', 'areas.index', actual('areas.*'), puedeVer('areas')),
                enlace('Catálogo de análisis', 'servicios.index', actual('servicios.index', 'servicios.edit'), puedeVer('servicios')),
            ],
        },
        {
            clave: 'historias',
            etiqueta: 'Historias Clínicas',
            activo: actual('historias-clinicas.*', 'cuestionario.*'),
            enlaces: [
                enlace('Listado de historias clínicas', 'historias-clinicas.index', actual('historias-clinicas.index'), puedeVer('historias-clinicas')),
                enlace('Nueva historia clínica', 'historias-clinicas.create', actual('historias-clinicas.create'), puedeVer('historias-clinicas') && puedeCrear('historias-clinicas')),
                enlace('Preguntas del cuestionario', 'cuestionario.index', actual('cuestionario.*'), puedeVer('cuestionario')),
            ],
        },
        {
            clave: 'diagnosticos',
            etiqueta: 'Diagnósticos',
            activo: false,
            pendiente: true,
            enlaces: [
                pendiente('Nuevo diagnóstico', puedeGranular('diagnosticos.agregar')),
                pendiente('Listado de diagnósticos', puedeAlguno(
                    'diagnosticos.agregar', 'diagnosticos.modificar', 'diagnosticos.emitir',
                    'diagnosticos.consultar_de_otros', 'diagnosticos.modificar_emitidos', 'diagnosticos.cancelar',
                )),
            ],
        },
        {
            clave: 'facturacion',
            etiqueta: 'Notas de pago',
            activo: actual('productos.*', 'notas-pago.*', 'clientes.*'),
            enlaces: [
                enlace('Nueva nota de pago', 'notas-pago.create', actual('notas-pago.create'), puedeGranular('facturacion.agregar_facturas')),
                enlace('Listado notas de pago', 'notas-pago.index', actual('notas-pago.index'), puedeAlguno('facturacion.agregar_facturas', 'facturacion.modificar_facturas', 'facturacion.cancelar_factura')),
                pendiente('Nueva nota de abono', puedeGranular('facturacion.agregar_notas_abono')),
                pendiente('Listado Notas de abono', puedeAlguno('facturacion.agregar_notas_abono', 'facturacion.modificar_notas_abono', 'facturacion.cancelar_nota_abono')),
                enlace('Listado de productos', 'productos.index', actual('productos.index', 'productos.edit'), puedeVer('productos')),
                enlace('Agregar producto', 'productos.create', actual('productos.create'), puedeCrear('productos')),
                enlace('Listado clientes', 'clientes.index', actual('clientes.*'), puedeVer('clientes')),
                pendiente('Estado de cuenta', puedeVer('clientes')),
            ],
        },
        {
            clave: 'reportes',
            etiqueta: 'Reportes',
            activo: false,
            pendiente: true,
            enlaces: [
                pendiente('Diagnósticos', puedeGranular('reportes.diagnosticos')),
                pendiente('Análisis por especie', puedeGranular('reportes.analisis_x_especie')),
                pendiente('Muestras Trabajadas X Especie', puedeGranular('reportes.muestras_trabajadas_x_especie')),
                pendiente('Accesos sospechosos', puedeGranular('reportes.accesos_sospechosos')),
                pendiente('Auditoría de Movimientos', puedeGranular('reportes.auditoria_movimientos')),
                pendiente('Notificación de res. de B-Agonistas', puedeGranular('reportes.notificacion_b_agonistas')),
                pendiente('Reporte global', puedeGranular('reportes.global')),
                pendiente('Estado de cuenta por cliente', puedeGranular('reportes.estado_cuenta_cliente')),
                pendiente('Estados Movimientos de notas de pago', puedeGranular('reportes.estados_movimientos_facturacion')),
                pendiente('Volumen de Trabajo', puedeGranular('reportes.volumen_trabajo')),
                pendiente('Muestras / Casos sin nota de pago', puedeGranular('reportes.muestras_casos_no_facturados')),
            ],
        },
        {
            clave: 'catalogos',
            etiqueta: 'Catálogos',
            activo: actual(
                'propietarios.*', 'municipios.index', 'estados.index', 'especies.*', 'razas.index',
                'pruebas.index', 'funcion-zootecnicas.*', 'metodos.*', 'tipos-muestra.*',
            ),
            enlaces: [
                ['propietarios',       'Propietarios',           'propietarios.*'],
                ['municipios',         'Municipios',             'municipios.index'],
                ['estados',            'Estados',                'estados.index'],
                ['especies',           'Especies',               'especies.index'],
                ['razas',              'Razas',                  'razas.index'],
                ['pruebas',            'Pruebas',                'pruebas.index'],
                ['funcion-zootecnicas','Funciones Zootécnicas',  'funcion-zootecnicas.*'],
                ['metodos',            'Métodos',                'metodos.*'],
                ['tipos-muestra',      'Tipos de Muestra',       'tipos-muestra.*'],
            ].map(([modulo, etiqueta, patron]) => enlace(etiqueta, `${modulo}.index`, actual(patron), puedeVer(modulo))),
        },
        {
            // Datos personales es visible para todo usuario.
            clave: 'seguridad',
            etiqueta: 'Seguridad',
            activo: actual('users.*', 'roles.*', 'datos-personales.*'),
            enlaces: [
                enlace('Datos personales', 'datos-personales.edit', actual('datos-personales.*'), true),
                enlace('Usuarios', 'users.index', actual('users.index', 'users.edit'), puedeVer('users')),
                enlace('Nuevo usuario', 'users.create', actual('users.create'), puedeCrear('users')),
                enlace('Roles', 'roles.index', actual('roles.*'), puedeVer('roles')),
            ],
        },
    ]
        .map((grupo) => ({ ...grupo, enlaces: grupo.enlaces.filter((e) => e.visible) }))
        .filter((grupo) => grupo.enlaces.length > 0);

    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

    // Grupos desplegados; al cargar se abre el que contiene la página actual.
    const [abiertos, setAbiertos] = useState(() =>
        Object.fromEntries(grupos.map((g) => [g.clave, g.activo])),
    );
    const alternar = (clave) => setAbiertos((a) => ({ ...a, [clave]: !a[clave] }));

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
            key={label}
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
            key={label}
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
                        Inicio
                    </NavLink>

                    {grupos.map((grupo) => (
                        <div key={grupo.clave}>
                            {groupBtn(grupo.etiqueta, abiertos[grupo.clave], () => alternar(grupo.clave), grupo.activo, grupo.pendiente)}
                            {abiertos[grupo.clave] && (
                                <div className="ms-3 mt-1 flex flex-col items-start space-y-1 border-l border-gray-200 ps-3">
                                    {grupo.enlaces.map((e) => (e.pendiente ? enlacePendiente(e.etiqueta) : (
                                        <NavLink key={e.etiqueta} href={e.href} active={e.activo}>{e.etiqueta}</NavLink>
                                    )))}
                                </div>
                            )}
                        </div>
                    ))}
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
                            <ResponsiveNavLink href={route('dashboard')} active={route().current('dashboard')}>Inicio</ResponsiveNavLink>
                            {grupos.map((grupo) => (
                                <div key={grupo.clave} className="space-y-1">
                                    <div className="flex items-center px-4 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                        {grupo.etiqueta}
                                        {grupo.pendiente && insigniaPendiente}
                                    </div>
                                    {grupo.enlaces.map((e) => (e.pendiente ? enlacePendienteMovil(e.etiqueta) : (
                                        <ResponsiveNavLink key={e.etiqueta} href={e.href} active={e.activo}>{e.etiqueta}</ResponsiveNavLink>
                                    )))}
                                </div>
                            ))}
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
