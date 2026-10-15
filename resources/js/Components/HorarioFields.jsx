import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';

const checkboxClass =
    'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500';

/**
 * Ventana de acceso (horas + días permitidos) con el formato que consulta
 * User::dentroDeHorario(). La comparten el formulario de usuarios y el de
 * roles, donde sirve de plantilla.
 */
export default function HorarioFields({ data, setData, errors, dias }) {
    function toggleDia(dia) {
        const actuales = data.dias_permitidos ?? [];
        setData(
            'dias_permitidos',
            actuales.includes(dia)
                ? actuales.filter((d) => d !== dia)
                : [...actuales, dia].sort((a, b) => a - b),
        );
    }

    return (
        <div className="border-t border-gray-200 pt-6">
            <h3 className="text-sm font-semibold text-gray-900">
                Horario de acceso
            </h3>
            <p className="mt-1 text-xs text-gray-500">
                Dejar las horas en blanco permite el acceso a cualquier hora. Si la
                hora final es menor que la inicial, la ventana cruza la medianoche.
            </p>

            <div className="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="hora_inicio" value="Hora de inicio" />
                    <input
                        id="hora_inicio"
                        type="time"
                        value={data.hora_inicio}
                        onChange={(e) => setData('hora_inicio', e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError message={errors.hora_inicio} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor="hora_fin" value="Hora de fin" />
                    <input
                        id="hora_fin"
                        type="time"
                        value={data.hora_fin}
                        onChange={(e) => setData('hora_fin', e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError message={errors.hora_fin} className="mt-2" />
                </div>
            </div>

            <div className="mt-4">
                <InputLabel value="Días permitidos" />
                <p className="mt-1 text-xs text-gray-500">
                    Sin ningún día marcado se permite el acceso todos los días.
                </p>
                <div className="mt-2 flex flex-wrap gap-3">
                    {Object.entries(dias).map(([numero, nombre]) => (
                        <label key={numero} className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={(data.dias_permitidos ?? []).includes(Number(numero))}
                                onChange={() => toggleDia(Number(numero))}
                                className={checkboxClass}
                            />
                            <span className="text-sm text-gray-700">{nombre}</span>
                        </label>
                    ))}
                </div>
                <InputError message={errors.dias_permitidos} className="mt-2" />
            </div>
        </div>
    );
}
