import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { useMemo, useState } from 'react';

// Se muestran como mucho estas coincidencias: con cientos de opciones, pintar
// todas en cada tecla vuelve lenta la lista. Escribir más acota el resultado.
const MAXIMO_OPCIONES = 50;

/** Minúsculas y sin acentos, para que "gomez" encuentre "Gómez". */
const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

/**
 * Selector en el que se escribe para filtrar las opciones, en lugar de un
 * <select> con la lista completa.
 *
 * `value` y el valor que recibe `onChange` son el id como texto ('' = nada
 * seleccionado), igual que con un <select>, para usarlo con useForm.
 *
 * @param {{ value: string|number, label: string }[]} options
 */
export default function ComboboxSelect({ id, value, onChange, options, placeholder = '', disabled = false, className = '' }) {
    const [query, setQuery] = useState('');

    const seleccionada = options.find((o) => String(o.value) === String(value)) ?? null;

    const filtradas = useMemo(() => {
        const palabras = normalizar(query).split(/\s+/).filter(Boolean);
        const coinciden = palabras.length
            ? options.filter((o) => {
                const etiqueta = normalizar(o.label);
                return palabras.every((p) => etiqueta.includes(p));
            })
            : options;

        return coinciden.slice(0, MAXIMO_OPCIONES);
    }, [options, query]);

    return (
        <Combobox
            value={seleccionada}
            onChange={(opcion) => {
                onChange(opcion ? String(opcion.value) : '');
                setQuery('');
            }}
            onClose={() => setQuery('')}
            disabled={disabled}
            immediate
        >
            <div className={`relative mt-1 ${className}`}>
                <ComboboxInput
                    id={id}
                    className="block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                    displayValue={(opcion) => opcion?.label ?? ''}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder={placeholder}
                    autoComplete="off"
                />
                <ComboboxButton className="absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                    <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fillRule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l3.25 3.5a.75.75 0 1 1-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 0 1-1.1-1.02l3.25-3.5A.75.75 0 0 1 10 3Zm-3.76 9.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" clipRule="evenodd" />
                    </svg>
                </ComboboxButton>

                <ComboboxOptions
                    anchor="bottom start"
                    className="z-50 max-h-60 w-[var(--input-width)] overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black/5 [--anchor-gap:4px] empty:invisible"
                >
                    {filtradas.length === 0 && query !== '' && (
                        <div className="px-3 py-2 text-gray-500">Sin coincidencias.</div>
                    )}
                    {filtradas.map((opcion) => (
                        <ComboboxOption
                            key={opcion.value}
                            value={opcion}
                            className="cursor-pointer select-none px-3 py-2 text-gray-900 data-[focus]:bg-indigo-50 data-[selected]:font-semibold"
                        >
                            {opcion.label}
                        </ComboboxOption>
                    ))}
                </ComboboxOptions>
            </div>
        </Combobox>
    );
}
