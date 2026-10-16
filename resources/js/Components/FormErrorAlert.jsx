import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const MENSAJE_INESPERADO = 'Ocurrió un error inesperado. Intenta de nuevo; si el problema continúa, contacta al administrador.';

function mensajePorEstado(status) {
    if (status === 419) return 'Tu sesión expiró. Recarga la página e intenta de nuevo.';
    if (status === 403) return 'No tienes permiso para realizar esta acción.';
    if (status === 404) return 'El registro ya no existe.';
    return MENSAJE_INESPERADO;
}

/**
 * Traduce el error de una petición axios al formato de errores del formulario:
 * los errores de validación (422) tal cual, cualquier otro fallo como `general`.
 */
export function erroresDeAxios(err) {
    const status = err.response?.status;
    if (status === 422 && err.response.data?.errors) return err.response.data.errors;
    if (!status || status >= 500) return { general: MENSAJE_INESPERADO };
    return { general: err.response.data?.message || mensajePorEstado(status) };
}

/**
 * Mensaje general en la parte superior de un formulario cuando falta algún
 * campo o algo sale mal. Acepta errores de useForm (string) o de axios
 * (string[]); la clave `general` se muestra como mensaje principal.
 *
 * Con `serverErrors` (por defecto) también escucha las visitas de Inertia que
 * fallan sin errores de validación (500, sesión expirada, sin conexión).
 */
export default function FormErrorAlert({ errors = {}, message = null, serverErrors = true, className = '' }) {
    const [errorServidor, setErrorServidor] = useState(null);
    const ref = useRef(null);

    useEffect(() => {
        if (!serverErrors) return;

        const quitarStart = router.on('start', () => setErrorServidor(null));
        const quitarInvalid = router.on('invalid', (event) => {
            const status = event.detail.response?.status;
            setErrorServidor(mensajePorEstado(status));
            // En producción se evita el modal con la página de error del servidor.
            if (import.meta.env.PROD) event.preventDefault();
        });
        const quitarException = router.on('exception', (event) => {
            setErrorServidor('No se pudo conectar con el servidor. Revisa tu conexión e intenta de nuevo.');
            event.preventDefault();
        });

        return () => {
            quitarStart();
            quitarInvalid();
            quitarException();
        };
    }, [serverErrors]);

    const { general, ...campos } = errors ?? {};
    const mensajesCampos = [...new Set(Object.values(campos).flat().filter(Boolean))];
    const mensajeGeneral = message || [general].flat().filter(Boolean)[0] || errorServidor;
    const visible = Boolean(mensajeGeneral) || mensajesCampos.length > 0;
    const firma = visible ? `${mensajeGeneral}|${mensajesCampos.join('|')}` : '';

    // Lleva el aviso a la vista cuando aparece o cambia, por si el formulario es largo.
    useEffect(() => {
        if (firma) ref.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, [firma]);

    if (!visible) return null;

    return (
        <div
            ref={ref}
            role="alert"
            className={'rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 ' + className}
        >
            <p className="font-medium">
                {mensajeGeneral ?? 'Hay errores en el formulario. Revisa los campos marcados.'}
            </p>
            {mensajesCampos.length > 0 && (
                <ul className="mt-1 list-inside list-disc">
                    {mensajesCampos.map((m) => (
                        <li key={m}>{m}</li>
                    ))}
                </ul>
            )}
        </div>
    );
}
