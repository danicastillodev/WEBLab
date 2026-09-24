import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { TIPOS } from './_form';

export default function Index({ preguntas }) {
    const { flash } = usePage().props;

    function handleDelete(pregunta) {
        const aviso = pregunta.respuestas_count > 0
            ? `"${pregunta.texto}" ya tiene ${pregunta.respuestas_count} respuesta(s) registrada(s), así que se desactivará en lugar de eliminarse. ¿Continuar?`
            : `¿Eliminar la pregunta "${pregunta.texto}"?`;

        if (confirm(aviso)) {
            router.delete(route('cuestionario.destroy', pregunta.id));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Cuestionario</h2>
                    <Link
                        href={route('cuestionario.create')}
                        className="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-hover"
                    >
                        Nueva pregunta
                    </Link>
                </div>
            }
        >
            <Head title="Cuestionario" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    <p className="mb-4 text-sm text-gray-500">
                        Estas preguntas aparecen en el formulario de nueva historia clínica. Las inactivas
                        dejan de mostrarse, pero conservan las respuestas ya capturadas.
                    </p>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Pregunta</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tipo</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Respuestas</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {preguntas.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-4 text-center text-sm text-gray-500">
                                            Aún no hay preguntas en el cuestionario.
                                        </td>
                                    </tr>
                                )}
                                {preguntas.map((pregunta) => (
                                    <tr key={pregunta.id} className={pregunta.activa ? '' : 'bg-gray-50'}>
                                        <td className="px-6 py-4 text-sm text-gray-400">{pregunta.orden}</td>
                                        <td className={`px-6 py-4 text-sm ${pregunta.activa ? 'font-medium text-gray-900' : 'text-gray-500'}`}>
                                            {pregunta.texto}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{TIPOS[pregunta.tipo] ?? pregunta.tipo}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">{pregunta.respuestas_count}</td>
                                        <td className="px-6 py-4 text-sm">
                                            <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset ${
                                                pregunta.activa
                                                    ? 'bg-green-50 text-green-800 ring-green-200'
                                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                                            }`}>
                                                <span className={`h-2 w-2 rounded-full ${pregunta.activa ? 'bg-green-500' : 'bg-gray-400'}`} />
                                                {pregunta.activa ? 'Activa' : 'Inactiva'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-right text-sm">
                                            <Link
                                                href={route('cuestionario.edit', pregunta.id)}
                                                className="mr-4 text-indigo-600 hover:text-indigo-900"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(pregunta)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                {pregunta.respuestas_count > 0 ? 'Desactivar' : 'Eliminar'}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
