import ApplicationLogo from '@/Components/ApplicationLogo';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

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

export default function Dashboard() {
    const { auth } = usePage().props;
    const mensaje = useMemo(() => getMensajeDelaSesion(), []);

    return (
        <AuthenticatedLayout>
            <Head title="Inicio" />

            <div className="flex min-h-[60vh] items-center justify-center py-12">
                <div className="mx-auto max-w-md text-center">
                    <div className="mb-6 flex justify-center">
                        <ApplicationLogo className="h-24 w-auto" />
                    </div>

                    <h1 className="text-3xl font-bold text-gray-800">
                        Bienvenido, {auth.user.name}
                    </h1>

                    <p className="mt-4 text-base text-gray-500">{mensaje}</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
