import ApplicationLogo from '@/Components/ApplicationLogo';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    ArrowRightIcon, BanknotesIcon, ClipboardDocumentListIcon, DocumentPlusIcon,
    IdentificationIcon, LightBulbIcon, UserIcon,
} from '@heroicons/react/24/outline';

const mensajes = [
    '🐄 Las vacas tienen casi 360° de visión panorámica, pero un punto ciego justo al frente.',
    '🐖 Los cerdos son más inteligentes que los perros y pueden aprender su nombre en apenas dos semanas.',
    '🐔 Las gallinas se comunican con sus polluelos incluso antes de que eclosionen, a través del cascarón.',
    '🐑 Las ovejas pueden recordar hasta 50 rostros de otras ovejas durante más de dos años.',
    '🐐 Las cabras tienen pupilas rectangulares que les permiten ver casi 340° a su alrededor.',
    '🐴 Los caballos duermen de pie gracias a un mecanismo de bloqueo en sus patas llamado "aparato suspensor".',
    '🐄 Una vaca produce en promedio 200,000 vasos de leche a lo largo de su vida.',
    '🐓 Los gallos cantan al amanecer para defender su territorio, incluso si no hay luz solar.',
    '🐷 Los cerdos se revuelcan en lodo no porque sean sucios, sino porque carecen de glándulas sudoríparas.',
    '🐂 Los bovinos pueden detectar olores a más de 10 km de distancia.',
    '🐑 El vellón de una oveja merino puede crecer más de 10 cm al año y pesa hasta 10 kg.',
    '🐎 Los caballos tienen el ojo más grande de todos los mamíferos terrestres.',
    '🐓 Las gallinas pueden ver más colores que los humanos, incluyendo luz ultravioleta.',
    '🐐 Las cabras son de los primeros animales domesticados por el ser humano, hace más de 10,000 años.',
];

const SESSION_KEY = 'weblab_dato_animal';

function getMensajeDelaSesion() {
    const guardado = sessionStorage.getItem(SESSION_KEY);
    if (guardado !== null) return guardado;
    const nuevo = mensajes[Math.floor(Math.random() * mensajes.length)];
    sessionStorage.setItem(SESSION_KEY, nuevo);
    return nuevo;
}

// Saludo según la hora local.
function saludo() {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 19) return 'Buenas tardes';
    return 'Buenas noches';
}

// Colores de las tarjetas de acceso rápido (clases completas para Tailwind).
const ESTILOS = {
    sky:     { ficha: 'bg-sky-100 text-sky-600 group-hover:bg-sky-500',             borde: 'hover:border-sky-300',     flecha: 'group-hover:text-sky-500' },
    indigo:  { ficha: 'bg-indigo-100 text-indigo-600 group-hover:bg-indigo-500',    borde: 'hover:border-indigo-300',  flecha: 'group-hover:text-indigo-500' },
    amber:   { ficha: 'bg-amber-100 text-amber-600 group-hover:bg-amber-500',       borde: 'hover:border-amber-300',   flecha: 'group-hover:text-amber-500' },
    teal:    { ficha: 'bg-teal-100 text-teal-600 group-hover:bg-teal-500',          borde: 'hover:border-teal-300',    flecha: 'group-hover:text-teal-500' },
    emerald: { ficha: 'bg-emerald-100 text-emerald-600 group-hover:bg-emerald-500', borde: 'hover:border-emerald-300', flecha: 'group-hover:text-emerald-500' },
    rose:    { ficha: 'bg-rose-100 text-rose-600 group-hover:bg-rose-500',          borde: 'hover:border-rose-300',    flecha: 'group-hover:text-rose-500' },
};

function AccesoRapido({ titulo, descripcion, href, icono: Icono, color, retardo }) {
    const e = ESTILOS[color];
    return (
        <Link
            href={href}
            style={{ animationDelay: `${retardo}ms` }}
            className={`group flex items-start gap-4 rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 motion-safe:animate-aparecer ${e.borde}`}
        >
            <span className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition duration-200 group-hover:text-white ${e.ficha}`}>
                <Icono className="h-6 w-6" aria-hidden="true" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block font-semibold text-gray-800">{titulo}</span>
                <span className="mt-0.5 block text-sm text-gray-500">{descripcion}</span>
            </span>
            <ArrowRightIcon className={`mt-1 h-4 w-4 shrink-0 text-gray-300 transition duration-200 group-hover:translate-x-1 ${e.flecha}`} aria-hidden="true" />
        </Link>
    );
}

export default function Dashboard() {
    const { auth } = usePage().props;
    const mensaje = useMemo(() => getMensajeDelaSesion(), []);

    const puedeVer = (modulo) => !!auth.permisos?.[modulo]?.ver;
    const puedeCrear = (modulo) => !!auth.permisos?.[modulo]?.crear;
    const puedeGranular = (clave) => !!auth.permisosGranulares?.[clave];

    // Mismos permisos que las opciones equivalentes del menú lateral.
    const accesos = [
        { titulo: 'Nueva historia clínica', descripcion: 'Registra un caso y sus muestras.', ruta: 'historias-clinicas.create', icono: DocumentPlusIcon, color: 'sky', visible: puedeVer('historias-clinicas') && puedeCrear('historias-clinicas') },
        { titulo: 'Historias clínicas', descripcion: 'Consulta y da seguimiento a los casos.', ruta: 'historias-clinicas.index', icono: ClipboardDocumentListIcon, color: 'indigo', visible: puedeVer('historias-clinicas') },
        { titulo: 'Nueva nota de pago', descripcion: 'Cobra los análisis de un caso.', ruta: 'notas-pago.create', icono: BanknotesIcon, color: 'amber', visible: puedeGranular('facturacion.agregar_facturas') },
        { titulo: 'Propietarios', descripcion: 'Catálogo de propietarios y explotaciones.', ruta: 'propietarios.index', icono: UserIcon, color: 'teal', visible: puedeVer('propietarios') },
        { titulo: 'Datos personales', descripcion: 'Actualiza tu información de contacto.', ruta: 'datos-personales.edit', icono: IdentificationIcon, color: 'rose', visible: true },
    ].filter((a) => a.visible);

    return (
        <AuthenticatedLayout>
            <Head title="Inicio" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
                {/* Banner de bienvenida */}
                <section className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-sky-600 to-teal-500 bg-[length:200%_200%] p-8 text-white shadow-xl shadow-indigo-500/20 motion-safe:animate-degradado sm:p-10">
                    {/* Círculos decorativos */}
                    <div className="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10" aria-hidden="true" />
                    <div className="pointer-events-none absolute -bottom-20 right-24 h-48 w-48 rounded-full bg-white/10" aria-hidden="true" />
                    <div className="pointer-events-none absolute bottom-6 left-1/2 h-16 w-16 rounded-full bg-white/10 motion-safe:animate-flotar" aria-hidden="true" />

                    <div className="relative flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                        <div className="flex h-28 w-28 shrink-0 items-center justify-center rounded-full bg-white/90 p-3 shadow-lg ring-4 ring-white/30">
                            <ApplicationLogo className="h-full w-auto" />
                        </div>
                        <div className="text-center sm:text-left">
                            <p className="text-sm font-medium uppercase tracking-widest text-white/80">{saludo()}</p>
                            <h1 className="mt-1 text-3xl font-bold sm:text-4xl">{auth.user.name}</h1>
                            <p className="mt-2 text-white/85">Bienvenido al Sistema de Gestión de Diagnósticos Clínicos.</p>
                        </div>
                    </div>
                </section>

                {/* Dato curioso */}
                <div className="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 motion-safe:animate-aparecer">
                    <LightBulbIcon className="mt-0.5 h-5 w-5 shrink-0 text-amber-500" aria-hidden="true" />
                    <p className="text-sm">
                        <span className="font-semibold">¿Sabías que…? </span>
                        {mensaje}
                    </p>
                </div>

                {/* Accesos rápidos */}
                {accesos.length > 0 && (
                    <section className="mt-8">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">Accesos rápidos</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {accesos.map((a, i) => (
                                <AccesoRapido key={a.ruta} {...a} href={route(a.ruta)} retardo={80 + i * 60} />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
