import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';

const checkboxClass =
    'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500';

const selectClass =
    'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

const dosDigitos = (n) => String(n).padStart(2, '0');
const HORAS = Array.from({ length: 24 }, (_, h) => dosDigitos(h));
const MINUTOS = Array.from({ length: 12 }, (_, i) => dosDigitos(i * 5));

/**
 * Hora en formato de 24 h (valor "HH:MM" o ''). Se usan dos listas en lugar de
 * <input type="time"> porque éste muestra a.m./p.m. según la configuración
 * regional del equipo y no hay forma de forzar las 24 h.
 */
function Hora24({ id, value, onChange }) {
    const [hora = '', minuto = ''] = value ? value.split(':') : [];
    // Un minuto guardado fuera de los saltos de 5 (p. ej. 08:07) sigue apareciendo.
    const minutos = minuto && !MINUTOS.includes(minuto) ? [...MINUTOS, minuto].sort() : MINUTOS;

    return (
        <div className="mt-1 flex items-center gap-2">
            <select
                id={id}
                aria-label="Hora"
                value={hora}
                onChange={(e) => onChange(e.target.value ? `${e.target.value}:${minuto || '00'}` : '')}
                className={selectClass}
            >
                <option value="">--</option>
                {HORAS.map((h) => (
                    <option key={h} value={h}>{h}</option>
                ))}
            </select>
            <span className="text-gray-500">:</span>
            <select
                aria-label="Minutos"
                value={minuto}
                disabled={!hora}
                onChange={(e) => onChange(`${hora}:${e.target.value}`)}
                className={selectClass}
            >
                {!hora && <option value="">--</option>}
                {minutos.map((m) => (
                    <option key={m} value={m}>{m}</option>
                ))}
            </select>
        </div>
    );
}

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
                Formato de 24 horas. Dejar las horas en blanco (--) permite el acceso a
                cualquier hora. Si la hora final es menor que la inicial, la ventana cruza
                la medianoche.
            </p>

            <div className="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="hora_inicio" value="Hora de inicio" />
                    <Hora24
                        id="hora_inicio"
                        value={data.hora_inicio}
                        onChange={(valor) => setData('hora_inicio', valor)}
                    />
                    <InputError message={errors.hora_inicio} className="mt-2" />
                </div>
                <div>
                    <InputLabel htmlFor="hora_fin" value="Hora de fin" />
                    <Hora24
                        id="hora_fin"
                        value={data.hora_fin}
                        onChange={(valor) => setData('hora_fin', valor)}
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
