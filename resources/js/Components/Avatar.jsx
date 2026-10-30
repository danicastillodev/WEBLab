/**
 * Imagen de perfil del usuario. Según user.avatar_tipo muestra la primera
 * letra del nombre ('inicial', por defecto), las iniciales de nombre y
 * apellido ('iniciales') o la foto subida ('foto').
 */
export function textoAvatar(nombre, tipo = 'inicial') {
    const palabras = (nombre ?? '').trim().split(/\s+/).filter(Boolean);
    if (palabras.length === 0) return '?';
    if (tipo !== 'iniciales' || palabras.length === 1) return palabras[0].charAt(0);

    // Convención: nombre(s) seguido de apellido paterno y materno. Con dos o
    // tres palabras el apellido es la segunda; con cuatro o más, la penúltima.
    const apellido = palabras.length >= 4 ? palabras[palabras.length - 2] : palabras[1];
    return palabras[0].charAt(0) + apellido.charAt(0);
}

export default function Avatar({ user, tipo, fotoUrl, className = 'h-9 w-9 text-sm' }) {
    const tipoEfectivo = tipo ?? user?.avatar_tipo ?? 'inicial';
    const url = fotoUrl ?? user?.avatar_url;

    if (tipoEfectivo === 'foto' && url) {
        return (
            <img
                src={url}
                alt=""
                className={`shrink-0 rounded-full object-cover shadow-md ${className}`}
            />
        );
    }

    return (
        <span
            className={`flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 via-sky-500 to-teal-500 font-bold uppercase text-white shadow-md ${className}`}
            aria-hidden="true"
        >
            {textoAvatar(user?.name, tipoEfectivo)}
        </span>
    );
}
