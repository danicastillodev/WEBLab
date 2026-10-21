/**
 * "2026-10-20 14:05:00" (o "2026-10-20T14:05:00...") → "20/10/2026 14:05".
 * Si el valor no trae hora, devuelve solo la fecha.
 */
export function formatFechaHora(valor) {
    if (!valor) return '';
    const texto = String(valor);
    const [y, m, d] = texto.slice(0, 10).split('-');
    if (!(y && m && d)) return texto;

    const hora = texto.slice(11, 16);
    return hora ? `${d}/${m}/${y} ${hora}` : `${d}/${m}/${y}`;
}

/**
 * "2026-10-20 14:05:00" (o "2026-10-20T14:05:00...") → "2026/10/20 14:05".
 * Si el valor no trae hora, devuelve solo la fecha.
 */
export function formatFechaHoraISO(valor) {
    if (!valor) return '';
    const texto = String(valor);
    const [y, m, d] = texto.slice(0, 10).split('-');
    if (!(y && m && d)) return texto;

    const hora = texto.slice(11, 16);
    return hora ? `${y}/${m}/${d} ${hora}` : `${y}/${m}/${d}`;
}
