/**
 * Insignia de estado de una historia clínica: punto de color + etiqueta.
 *
 * Único lugar donde vive el color de cada estado. Para agregar uno nuevo basta
 * con sumar su entrada aquí y al enum de la migración correspondiente.
 */
export const ESTADOS = {
    pendiente:  { etiqueta: 'Pendiente',  punto: 'bg-yellow-400', texto: 'text-yellow-800', fondo: 'bg-yellow-50 ring-yellow-200' },
    en_proceso: { etiqueta: 'En proceso', punto: 'bg-blue-500',   texto: 'text-blue-800',   fondo: 'bg-blue-50 ring-blue-200' },
    concluida:  { etiqueta: 'Concluida',  punto: 'bg-green-500',  texto: 'text-green-800',  fondo: 'bg-green-50 ring-green-200' },
    cancelada:  { etiqueta: 'Cancelada',  punto: 'bg-gray-400',   texto: 'text-gray-700',   fondo: 'bg-gray-100 ring-gray-200' },
    resultado_parcial: { etiqueta: 'Resultado parcial', punto: 'bg-orange-400', texto: 'text-orange-800', fondo: 'bg-orange-50 ring-orange-200' },
};

const DESCONOCIDO = { etiqueta: 'Sin estado', punto: 'bg-gray-300', texto: 'text-gray-500', fondo: 'bg-gray-50 ring-gray-200' };

export default function EstadoBadge({ estado, className = '' }) {
    const { etiqueta, punto, texto, fondo } = ESTADOS[estado] ?? DESCONOCIDO;

    return (
        <span
            className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset ${fondo} ${texto} ${className}`}
        >
            <span className={`h-2 w-2 shrink-0 rounded-full ${punto}`} aria-hidden="true" />
            {etiqueta}
        </span>
    );
}
