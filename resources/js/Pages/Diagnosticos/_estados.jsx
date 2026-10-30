/**
 * Estados de un análisis en Diagnósticos. "Pendiente" es el análisis sin
 * diagnóstico; los demás son el estado guardado del diagnóstico.
 */
export const ESTADOS_ANALISIS = {
    pendiente:  { etiqueta: 'Pendiente',  punto: 'bg-yellow-400', texto: 'text-yellow-800', fondo: 'bg-yellow-50 ring-yellow-200' },
    en_proceso: { etiqueta: 'En proceso', punto: 'bg-blue-500',   texto: 'text-blue-800',   fondo: 'bg-blue-50 ring-blue-200' },
    emitido:    { etiqueta: 'Emitido',    punto: 'bg-green-500',  texto: 'text-green-800',  fondo: 'bg-green-50 ring-green-200' },
    cancelado:  { etiqueta: 'Cancelado',  punto: 'bg-gray-400',   texto: 'text-gray-700',   fondo: 'bg-gray-100 ring-gray-200' },
};

export function EstadoAnalisis({ estado }) {
    const { etiqueta, punto, texto, fondo } = ESTADOS_ANALISIS[estado ?? 'pendiente'] ?? ESTADOS_ANALISIS.pendiente;

    return (
        <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset ${fondo} ${texto}`}>
            <span className={`h-2 w-2 shrink-0 rounded-full ${punto}`} aria-hidden="true" />
            {etiqueta}
        </span>
    );
}
