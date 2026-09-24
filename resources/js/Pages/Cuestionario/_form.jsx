import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export const TIPOS = {
    texto:  'Texto libre',
    si_no:  'Sí / No',
    numero: 'Número',
};

/** Campos compartidos por las pantallas de alta y edición de preguntas. */
export default function PreguntaForm({ data, setData, errors, processing, submit, etiquetaBoton, respuestasCount = 0 }) {
    return (
        <form onSubmit={submit} className="space-y-6">
            <div>
                <InputLabel htmlFor="texto" value="Pregunta" />
                <textarea
                    id="texto"
                    value={data.texto}
                    onChange={(e) => setData('texto', e.target.value)}
                    rows={3}
                    maxLength={500}
                    autoFocus
                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <div className="mt-1 text-right text-xs text-gray-400">{data.texto.length}/500</div>
                <InputError message={errors.texto} className="mt-1" />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="tipo" value="Tipo de respuesta" />
                    <select
                        id="tipo"
                        value={data.tipo}
                        onChange={(e) => setData('tipo', e.target.value)}
                        className={selectClass}
                    >
                        {Object.entries(TIPOS).map(([valor, etiqueta]) => (
                            <option key={valor} value={valor}>{etiqueta}</option>
                        ))}
                    </select>
                    <InputError message={errors.tipo} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor="orden" value="Orden" />
                    <TextInput
                        id="orden"
                        type="number"
                        min="0"
                        value={data.orden}
                        onChange={(e) => setData('orden', e.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.orden} className="mt-2" />
                </div>
            </div>

            <label className="flex items-start gap-3">
                <input
                    type="checkbox"
                    checked={data.activa}
                    onChange={(e) => setData('activa', e.target.checked)}
                    className="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span className="text-sm text-gray-700">
                    Activa
                    <span className="block text-xs text-gray-500">
                        Sólo las preguntas activas aparecen en el formulario de nueva historia clínica.
                        {respuestasCount > 0 && ` Esta pregunta ya tiene ${respuestasCount} respuesta(s) registrada(s); desactivarla no las borra.`}
                    </span>
                </span>
            </label>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>{etiquetaBoton}</PrimaryButton>
                <Link href={route('cuestionario.index')} className="text-sm text-gray-600 hover:text-gray-900">
                    Cancelar
                </Link>
            </div>
        </form>
    );
}
