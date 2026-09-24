import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head } from '@inertiajs/react';

// La tarjeta pide explícitamente sólo la página, sin funcionalidad todavía
// ("no agregues la funcionalidad aun"), así que los botones no tienen acción:
// respaldar/restaurar/reparar quedan para un card posterior que la especifique.
const secciones = [
    {
        titulo: 'Respaldar',
        texto: 'Por motivos de seguridad, debe hacer un respaldo de forma gradual para asegurar la disponibilidad de los datos. Si desea hacer el respaldo ahora por favor haga click en el botón que aparece a continuación.',
        boton: 'Respaldar',
    },
    {
        titulo: 'Restaurar',
        texto: 'Si ha ocurrido un error irrecuperable en la base de datos y no se ha podido reparar, haga click en el botón siguiente para restaurar la base de datos desde un respaldo anterior.',
        boton: 'Restaurar',
    },
    {
        titulo: 'Reparar',
        texto: 'Si no puede conectarse a la base de datos, haga click en reparar para intentar reparar la base de datos; en caso de que no sea posible la reparación, restaure a una copia anterior.',
        boton: 'Reparar',
    },
];

export default function Index() {
    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Mantenimiento de Base de Datos</h2>}
        >
            <Head title="Mantenimiento de Base de Datos" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <h3 className="mb-6 text-sm font-semibold text-gray-800">Opciones de mantenimiento de base de datos</h3>

                        <div className="space-y-6">
                            {secciones.map(({ titulo, texto, boton }) => (
                                <div key={titulo} className="rounded-lg border border-gray-200 p-4">
                                    <h4 className="mb-2 text-sm font-semibold text-gray-700">{titulo}</h4>
                                    <p className="mb-4 text-sm text-gray-500">{texto}</p>
                                    <SecondaryButton>{boton}</SecondaryButton>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
