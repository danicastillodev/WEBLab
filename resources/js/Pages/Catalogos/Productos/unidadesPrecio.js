export const UNIDADES_PRECIO = [
    { value: 'muestra', label: 'Por muestra' },
    { value: 'grupo', label: 'Por grupo' },
    { value: 'analisis', label: 'Por análisis' },
];

export function etiquetaUnidad(valor) {
    const unidad = UNIDADES_PRECIO.find((u) => u.value === valor);
    return unidad ? unidad.label.toLowerCase() : '';
}
