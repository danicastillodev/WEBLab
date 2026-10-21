import useTema from '@/hooks/useTema';

const OPCIONES = [
    {
        valor: 'claro',
        etiqueta: 'Claro',
        icono: 'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z',
    },
    {
        valor: 'oscuro',
        etiqueta: 'Oscuro',
        icono: 'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z',
    },
];

// Interruptor entre el tema claro y el oscuro de la interfaz.
export default function SelectorTema({ className = '' }) {
    const [tema, setTema] = useTema();

    return (
        <div role="radiogroup" aria-label="Tema" className={`flex rounded-md bg-gray-100 p-1 ${className}`}>
            {OPCIONES.map(({ valor, etiqueta, icono }) => (
                <button
                    key={valor}
                    type="button"
                    role="radio"
                    aria-checked={tema === valor}
                    onClick={() => setTema(valor)}
                    className={`flex flex-1 items-center justify-center gap-1.5 rounded px-2 py-1 text-xs font-medium transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 ${
                        tema === valor ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
                    }`}
                >
                    <svg className="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d={icono} />
                    </svg>
                    {etiqueta}
                </button>
            ))}
        </div>
    );
}
