import ApplicationLogo from '@/Components/ApplicationLogo';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import SelectorTema from '@/Components/SelectorTema';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    AdjustmentsHorizontalIcon, ArrowRightStartOnRectangleIcon, ArrowsRightLeftIcon, ArrowUturnLeftIcon,
    BanknotesIcon, BeakerIcon, BellAlertIcon, BookOpenIcon, BriefcaseIcon, BuildingLibraryIcon,
    BuildingOffice2Icon, ChartBarIcon, ChartBarSquareIcon, ChartPieIcon, CheckBadgeIcon, CircleStackIcon,
    ClipboardDocumentCheckIcon, ClipboardDocumentListIcon, Cog6ToothIcon, CubeIcon, CurrencyDollarIcon,
    DocumentChartBarIcon, DocumentMinusIcon, DocumentPlusIcon, DocumentTextIcon, ExclamationTriangleIcon,
    EyeDropperIcon, FingerPrintIcon, GlobeAltIcon, HomeIcon, IdentificationIcon, KeyIcon, ListBulletIcon,
    MapIcon, PlusCircleIcon, PresentationChartBarIcon, QuestionMarkCircleIcon, ReceiptPercentIcon,
    ReceiptRefundIcon, ScaleIcon, ShieldCheckIcon, Squares2X2Icon, SwatchIcon, TagIcon, UserCircleIcon,
    UserGroupIcon, UserIcon, UserPlusIcon, UsersIcon,
} from '@heroicons/react/24/outline';

// Color de acento de cada sección del menú. Las clases van completas para
// que Tailwind las detecte al compilar.
const ACENTOS = {
    indigo:  { ficha: 'bg-indigo-100 text-indigo-600',   solida: 'bg-indigo-500 text-white shadow-indigo-500/30',   activo: 'bg-indigo-50 text-indigo-700',   icono: 'text-indigo-500',  hover: 'group-hover:text-indigo-500',  barra: 'bg-indigo-500' },
    violet:  { ficha: 'bg-violet-100 text-violet-600',   solida: 'bg-violet-500 text-white shadow-violet-500/30',   activo: 'bg-violet-50 text-violet-700',   icono: 'text-violet-500',  hover: 'group-hover:text-violet-500',  barra: 'bg-violet-500' },
    sky:     { ficha: 'bg-sky-100 text-sky-600',         solida: 'bg-sky-500 text-white shadow-sky-500/30',         activo: 'bg-sky-50 text-sky-700',         icono: 'text-sky-500',     hover: 'group-hover:text-sky-500',     barra: 'bg-sky-500' },
    emerald: { ficha: 'bg-emerald-100 text-emerald-600', solida: 'bg-emerald-500 text-white shadow-emerald-500/30', activo: 'bg-emerald-50 text-emerald-700', icono: 'text-emerald-500', hover: 'group-hover:text-emerald-500', barra: 'bg-emerald-500' },
    amber:   { ficha: 'bg-amber-100 text-amber-600',     solida: 'bg-amber-500 text-white shadow-amber-500/30',     activo: 'bg-amber-50 text-amber-700',     icono: 'text-amber-500',   hover: 'group-hover:text-amber-500',   barra: 'bg-amber-500' },
    fuchsia: { ficha: 'bg-fuchsia-100 text-fuchsia-600', solida: 'bg-fuchsia-500 text-white shadow-fuchsia-500/30', activo: 'bg-fuchsia-50 text-fuchsia-700', icono: 'text-fuchsia-500', hover: 'group-hover:text-fuchsia-500', barra: 'bg-fuchsia-500' },
    teal:    { ficha: 'bg-teal-100 text-teal-600',       solida: 'bg-teal-500 text-white shadow-teal-500/30',       activo: 'bg-teal-50 text-teal-700',       icono: 'text-teal-500',    hover: 'group-hover:text-teal-500',    barra: 'bg-teal-500' },
    rose:    { ficha: 'bg-rose-100 text-rose-600',       solida: 'bg-rose-500 text-white shadow-rose-500/30',       activo: 'bg-rose-50 text-rose-700',       icono: 'text-rose-500',    hover: 'group-hover:text-rose-500',    barra: 'bg-rose-500' },
};

const acentoDe = (nombre) => ACENTOS[nombre] ?? ACENTOS.indigo;

// Icono de línea del menú; crece un poco al pasar el cursor por su enlace.
function Icono({ icono: Componente, className = '' }) {
    if (!Componente) return null;
    return <Componente className={`h-4 w-4 shrink-0 transition-transform duration-150 group-hover:scale-110 ${className}`} aria-hidden="true" />;
}

// Enlace del menú lateral (escritorio) con el acento de su sección. Los
// enlaces pendientes no navegan y llevan la insignia "Próx.".
function EnlaceMenu({ href = '#', activo = false, acento, icono, pendiente = false, insignia, children, ...props }) {
    const a = acentoDe(acento);
    const estado = pendiente
        ? 'font-medium text-amber-700/70 hover:bg-amber-50/60 hover:text-amber-700'
        : activo
            ? `${a.activo} font-semibold shadow-sm`
            : 'font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900';

    return (
        <Link
            href={href}
            onClick={pendiente ? (e) => e.preventDefault() : undefined}
            {...props}
            className={`group relative flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-sm transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 ${estado}`}
        >
            {activo && <span className={`absolute -left-[13px] top-1/2 h-5 w-1 -translate-y-1/2 rounded-full ${a.barra}`} aria-hidden="true" />}
            <Icono icono={icono} className={activo ? a.icono : pendiente ? '' : `text-gray-400 ${a.hover}`} />
            <span className="flex min-w-0 flex-1 items-center justify-between">
                <span>{children}</span>
                {pendiente && insignia}
            </span>
        </Link>
    );
}

export default function AuthenticatedLayout({ header, children }) {
    const { user, permisos, permisosGranulares } = usePage().props.auth;

    const puedeVer   = (modulo) => !!permisos?.[modulo]?.ver;
    const puedeCrear = (modulo) => !!permisos?.[modulo]?.crear;
    const puedeGranular = (clave) => !!permisosGranulares?.[clave];
    const puedeAlguno = (...claves) => claves.some(puedeGranular);
    const actual = (...patrones) => patrones.some((p) => route().current(p));

    // Enlace con pantalla real: { etiqueta, href, activo, visible, icono }.
    // Enlace pendiente (sin pantalla aún): { etiqueta, pendiente: true, visible, icono }.
    const enlace = (etiqueta, nombreRuta, activo, visible, icono) => ({
        etiqueta,
        href: visible ? route(nombreRuta) : '#',
        activo,
        visible,
        icono,
    });
    const pendiente = (etiqueta, visible, icono) => ({ etiqueta, pendiente: true, visible, icono });

    // Menú lateral. Cada enlace sólo se muestra si el usuario tiene permiso
    // para él, y un grupo sin ningún enlace visible se oculta por completo.
    const grupos = [
        {
            clave: 'configuracion',
            acento: 'violet',
            icono: Cog6ToothIcon,
            etiqueta: 'Configuración General',
            activo: actual('areas.*', 'datos-generales.*', 'parametros-operacion.*', 'mantenimiento-base-datos.*', 'servicios.*'),
            enlaces: [
                enlace('Datos generales', 'datos-generales.edit', actual('datos-generales.*'), puedeVer('datos-generales'), BuildingOffice2Icon),
                enlace('Mantenimiento de la base de datos', 'mantenimiento-base-datos.index', actual('mantenimiento-base-datos.*'), puedeVer('mantenimiento-base-datos'), CircleStackIcon),
                enlace('Parámetros de Operación', 'parametros-operacion.edit', actual('parametros-operacion.*'), puedeVer('parametros-operacion'), AdjustmentsHorizontalIcon),
                enlace('Catálogo de Áreas', 'areas.index', actual('areas.*'), puedeVer('areas'), Squares2X2Icon),
                enlace('Catálogo de análisis', 'servicios.index', actual('servicios.index', 'servicios.edit'), puedeVer('servicios'), BeakerIcon),
            ],
        },
        {
            clave: 'historias',
            acento: 'sky',
            icono: ClipboardDocumentListIcon,
            etiqueta: 'Historias Clínicas',
            activo: actual('historias-clinicas.*', 'cuestionario.*'),
            enlaces: [
                enlace('Listado de historias clínicas', 'historias-clinicas.index', actual('historias-clinicas.index'), puedeVer('historias-clinicas'), ListBulletIcon),
                enlace('Nueva historia clínica', 'historias-clinicas.create', actual('historias-clinicas.create'), puedeVer('historias-clinicas') && puedeCrear('historias-clinicas'), DocumentPlusIcon),
                enlace('Preguntas del cuestionario', 'cuestionario.index', actual('cuestionario.*'), puedeVer('cuestionario'), QuestionMarkCircleIcon),
            ],
        },
        {
            clave: 'diagnosticos',
            acento: 'emerald',
            icono: BeakerIcon,
            etiqueta: 'Diagnósticos',
            activo: false,
            pendiente: true,
            enlaces: [
                pendiente('Nuevo diagnóstico', puedeGranular('diagnosticos.agregar'), DocumentPlusIcon),
                pendiente('Listado de diagnósticos', puedeAlguno(
                    'diagnosticos.agregar', 'diagnosticos.modificar', 'diagnosticos.emitir',
                    'diagnosticos.consultar_de_otros', 'diagnosticos.modificar_emitidos', 'diagnosticos.cancelar',
                ), DocumentTextIcon),
            ],
        },
        {
            clave: 'facturacion',
            acento: 'amber',
            icono: BanknotesIcon,
            etiqueta: 'Notas de pago',
            activo: actual('productos.*', 'notas-pago.*', 'clientes.*'),
            enlaces: [
                enlace('Nueva nota de pago', 'notas-pago.create', actual('notas-pago.create'), puedeGranular('facturacion.agregar_facturas'), DocumentPlusIcon),
                enlace('Listado notas de pago', 'notas-pago.index', actual('notas-pago.index'), puedeAlguno('facturacion.agregar_facturas', 'facturacion.modificar_facturas', 'facturacion.cancelar_factura'), ReceiptPercentIcon),
                pendiente('Nueva nota de abono', puedeGranular('facturacion.agregar_notas_abono'), ReceiptRefundIcon),
                pendiente('Listado Notas de abono', puedeAlguno('facturacion.agregar_notas_abono', 'facturacion.modificar_notas_abono', 'facturacion.cancelar_nota_abono'), ArrowUturnLeftIcon),
                enlace('Listado de productos', 'productos.index', actual('productos.index', 'productos.edit'), puedeVer('productos'), CubeIcon),
                enlace('Agregar producto', 'productos.create', actual('productos.create'), puedeCrear('productos'), PlusCircleIcon),
                enlace('Listado clientes', 'clientes.index', actual('clientes.*'), puedeVer('clientes'), UserGroupIcon),
                pendiente('Estado de cuenta', puedeVer('clientes'), ScaleIcon),
            ],
        },
        {
            clave: 'reportes',
            acento: 'fuchsia',
            icono: ChartBarIcon,
            etiqueta: 'Reportes',
            activo: false,
            pendiente: true,
            enlaces: [
                pendiente('Diagnósticos', puedeGranular('reportes.diagnosticos'), DocumentChartBarIcon),
                pendiente('Análisis por especie', puedeGranular('reportes.analisis_x_especie'), PresentationChartBarIcon),
                pendiente('Muestras Trabajadas X Especie', puedeGranular('reportes.muestras_trabajadas_x_especie'), ChartPieIcon),
                pendiente('Accesos sospechosos', puedeGranular('reportes.accesos_sospechosos'), ExclamationTriangleIcon),
                pendiente('Auditoría de Movimientos', puedeGranular('reportes.auditoria_movimientos'), FingerPrintIcon),
                pendiente('Notificación de res. de B-Agonistas', puedeGranular('reportes.notificacion_b_agonistas'), BellAlertIcon),
                pendiente('Reporte global', puedeGranular('reportes.global'), GlobeAltIcon),
                pendiente('Estado de cuenta por cliente', puedeGranular('reportes.estado_cuenta_cliente'), CurrencyDollarIcon),
                pendiente('Estados Movimientos de notas de pago', puedeGranular('reportes.estados_movimientos_facturacion'), ArrowsRightLeftIcon),
                pendiente('Volumen de Trabajo', puedeGranular('reportes.volumen_trabajo'), ChartBarSquareIcon),
                pendiente('Muestras / Casos sin nota de pago', puedeGranular('reportes.muestras_casos_no_facturados'), DocumentMinusIcon),
            ],
        },
        {
            clave: 'catalogos',
            acento: 'teal',
            icono: BookOpenIcon,
            etiqueta: 'Catálogos',
            activo: actual(
                'propietarios.*', 'municipios.index', 'estados.index', 'especies.*', 'razas.index',
                'pruebas.index', 'funcion-zootecnicas.*', 'metodos.*', 'tipos-muestra.*',
            ),
            enlaces: [
                ['propietarios',       'Propietarios',           'propietarios.*', UserIcon],
                ['municipios',         'Municipios',             'municipios.index', BuildingLibraryIcon],
                ['estados',            'Estados',                'estados.index', MapIcon],
                ['especies',           'Especies',               'especies.index', TagIcon],
                ['razas',              'Razas',                  'razas.index', SwatchIcon],
                ['pruebas',            'Pruebas',                'pruebas.index', CheckBadgeIcon],
                ['funcion-zootecnicas','Funciones Zootécnicas',  'funcion-zootecnicas.*', BriefcaseIcon],
                ['metodos',            'Métodos',                'metodos.*', ClipboardDocumentCheckIcon],
                ['tipos-muestra',      'Tipos de Muestra',       'tipos-muestra.*', EyeDropperIcon],
            ].map(([modulo, etiqueta, patron, icono]) => enlace(etiqueta, `${modulo}.index`, actual(patron), puedeVer(modulo), icono)),
        },
        {
            // Datos personales es visible para todo usuario.
            clave: 'seguridad',
            acento: 'rose',
            icono: ShieldCheckIcon,
            etiqueta: 'Seguridad',
            activo: actual('users.*', 'roles.*', 'datos-personales.*'),
            enlaces: [
                enlace('Datos personales', 'datos-personales.edit', actual('datos-personales.*'), true, IdentificationIcon),
                enlace('Usuarios', 'users.index', actual('users.index', 'users.edit'), puedeVer('users'), UsersIcon),
                enlace('Nuevo usuario', 'users.create', actual('users.create'), puedeCrear('users'), UserPlusIcon),
                enlace('Roles', 'roles.index', actual('roles.*'), puedeVer('roles'), KeyIcon),
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
            className={`h-4 w-4 text-gray-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
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

    // Ficha de color con el icono de la sección; se rellena cuando la
    // sección contiene la página actual.
    const ficha = (icono, acento, activa, tam = 'h-7 w-7') => {
        const a = acentoDe(acento);
        return (
            <span className={`flex ${tam} shrink-0 items-center justify-center rounded-lg transition duration-200 group-hover:scale-105 ${activa ? `${a.solida} shadow-md` : a.ficha}`}>
                <Icono icono={icono} />
            </span>
        );
    };

    const botonGrupo = (grupo) => (
        <button
            type="button"
            onClick={() => alternar(grupo.clave)}
            aria-expanded={!!abiertos[grupo.clave]}
            className={`group flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 ${
                grupo.activo ? 'text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            }`}
        >
            <span className="flex items-center gap-2.5">
                {ficha(grupo.icono, grupo.acento, grupo.activo)}
                {grupo.etiqueta}
                {grupo.pendiente && insigniaPendiente}
            </span>
            {chevron(abiertos[grupo.clave])}
        </button>
    );

    const enlaceMovil = (e, acento) => (
        <ResponsiveNavLink
            key={e.etiqueta}
            href={e.href ?? '#'}
            active={!!e.activo}
            onClick={e.pendiente ? (ev) => ev.preventDefault() : undefined}
            className={`group ${e.pendiente ? '!text-amber-700/70' : ''}`}
        >
            <span className="flex w-full items-center justify-between">
                <span className="flex items-center gap-2">
                    <Icono icono={e.icono} className={e.activo ? acentoDe(acento).icono : 'text-gray-400'} />
                    {e.etiqueta}
                </span>
                {e.pendiente && insigniaPendiente}
            </span>
        </ResponsiveNavLink>
    );

    // Ruta actual (sin la consulta): reinicia la animación de entrada al
    // cambiar de pantalla, pero no al filtrar o paginar en la misma.
    const ruta = usePage().url.split('?')[0];

    return (
        <div className="relative flex min-h-screen bg-gray-100 bg-gradient-to-br from-indigo-50 via-sky-50 to-teal-50 dark:bg-none">
            {/* Manchas de color difuminadas al fondo (sólo tema claro). */}
            <div className="pointer-events-none fixed inset-0 overflow-hidden dark:hidden" aria-hidden="true">
                <div className="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-sky-300/30 blur-3xl motion-safe:animate-flotar" />
                <div className="absolute -bottom-32 left-1/3 h-[28rem] w-[28rem] rounded-full bg-teal-300/25 blur-3xl motion-safe:animate-flotar-lento" />
                <div className="absolute left-1/4 top-1/3 h-72 w-72 rounded-full bg-indigo-300/20 blur-3xl motion-safe:animate-flotar" />
            </div>

            {/* Sidebar */}
            <aside className="relative z-10 hidden w-64 flex-shrink-0 flex-col border-r border-gray-200/70 bg-white/90 shadow-md backdrop-blur sm:flex">
                {/* Franja de acento con los colores del logotipo. */}
                <div className="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-teal-500" aria-hidden="true" />

                {/* Logo */}
                <div className="flex items-center justify-center border-b border-gray-100 bg-gradient-to-b from-sky-50 to-transparent px-6 py-4 dark:bg-none">
                    <Link href="/" className="transition-transform duration-300 hover:scale-105">
                        <ApplicationLogo className="h-20 w-auto opacity-90" />
                    </Link>
                </div>

                {/* Nav links */}
                <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    <Link
                        href={route('dashboard')}
                        className={`group flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 ${
                            route().current('dashboard') ? 'text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        }`}
                    >
                        {ficha(HomeIcon, 'indigo', route().current('dashboard'))}
                        Inicio
                    </Link>

                    {grupos.map((grupo) => (
                        <div key={grupo.clave}>
                            {botonGrupo(grupo)}
                            {abiertos[grupo.clave] && (
                                <div className="mb-1 ms-[1.4rem] mt-1 flex flex-col space-y-0.5 border-l border-gray-200 ps-3 motion-safe:animate-desplegar">
                                    {grupo.enlaces.map((e) => (
                                        <EnlaceMenu
                                            key={e.etiqueta}
                                            href={e.pendiente ? '#' : e.href}
                                            activo={!!e.activo}
                                            pendiente={!!e.pendiente}
                                            acento={grupo.acento}
                                            icono={e.icono}
                                            insignia={insigniaPendiente}
                                        >
                                            {e.etiqueta}
                                        </EnlaceMenu>
                                    ))}
                                </div>
                            )}
                        </div>
                    ))}
                </nav>

                {/* User section at bottom */}
                <div className="border-t border-gray-200 px-3 py-4">
                    <div className="mb-3 flex items-center gap-3 px-1">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 via-sky-500 to-teal-500 text-sm font-bold uppercase text-white shadow-md" aria-hidden="true">
                            {user.name?.trim().charAt(0) ?? '?'}
                        </span>
                        <div className="min-w-0">
                            <div className="truncate text-sm font-medium text-gray-800">{user.name}</div>
                            <div className="truncate text-xs text-gray-500">{user.email}</div>
                        </div>
                    </div>
                    <SelectorTema className="mb-2" />
                    <div className="space-y-0.5">
                        <EnlaceMenu href={route('profile.edit')} activo={route().current('profile.edit')} acento="indigo" icono={UserCircleIcon}>
                            Perfil
                        </EnlaceMenu>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="group flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-start text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-red-50 hover:text-red-700"
                        >
                            <Icono icono={ArrowRightStartOnRectangleIcon} className="text-gray-400 group-hover:text-red-500" />
                            Cerrar sesión
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <div className="relative z-10 flex min-w-0 flex-1 flex-col">
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
                <div className="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-teal-500 sm:hidden" aria-hidden="true" />

                {/* Mobile menu */}
                {showingNavigationDropdown && (
                    <div className="bg-white sm:hidden">
                        <div className="space-y-1 pb-3 pt-2">
                            <ResponsiveNavLink href={route('dashboard')} active={route().current('dashboard')} className="group">
                                <span className="flex items-center gap-2">
                                    <Icono icono={HomeIcon} className="text-indigo-500" />
                                    Inicio
                                </span>
                            </ResponsiveNavLink>
                            {grupos.map((grupo) => (
                                <div key={grupo.clave} className="space-y-1">
                                    <div className="flex items-center gap-2 px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        {ficha(grupo.icono, grupo.acento, grupo.activo, 'h-6 w-6')}
                                        {grupo.etiqueta}
                                        {grupo.pendiente && insigniaPendiente}
                                    </div>
                                    {grupo.enlaces.map((e) => enlaceMovil(e, grupo.acento))}
                                </div>
                            ))}
                        </div>

                        <div className="border-t border-gray-200 pb-1 pt-4">
                            <div className="px-4">
                                <div className="text-base font-medium text-gray-800">{user.name}</div>
                                <div className="text-sm font-medium text-gray-500">{user.email}</div>
                            </div>
                            <SelectorTema className="mx-4 mt-3" />
                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('profile.edit')} className="group">
                                    <span className="flex items-center gap-2"><Icono icono={UserCircleIcon} className="text-gray-400" />Perfil</span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink method="post" href={route('logout')} as="button" className="group">
                                    <span className="flex items-center gap-2"><Icono icono={ArrowRightStartOnRectangleIcon} className="text-gray-400" />Cerrar sesión</span>
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </div>
                )}

                {header && (
                    <header className="relative bg-white/85 shadow-sm backdrop-blur">
                        <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                        <div className="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-indigo-500/70 via-sky-500/70 to-teal-500/70" aria-hidden="true" />
                    </header>
                )}

                <main key={ruta} className="flex-1 motion-safe:animate-aparecer">{children}</main>
            </div>
        </div>
    );
}
