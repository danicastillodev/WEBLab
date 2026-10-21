import { useEffect, useState } from 'react';

const CLAVE = 'tema';
const EVENTO = 'tema-cambiado';

const leer = () => {
    try {
        return localStorage.getItem(CLAVE) === 'oscuro' ? 'oscuro' : 'claro';
    } catch {
        return 'claro';
    }
};

// Tema de la interfaz ('claro' | 'oscuro'), guardado en el navegador.
// La clase `dark` de <html> también la aplica app.blade.php al cargar.
export default function useTema() {
    const [tema, setTema] = useState(leer);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', tema === 'oscuro');
        try {
            localStorage.setItem(CLAVE, tema);
        } catch {
            // Sin almacenamiento: el tema dura sólo esta visita.
        }
        // Mantiene sincronizados los selectores montados a la vez (menú lateral y móvil).
        window.dispatchEvent(new CustomEvent(EVENTO, { detail: tema }));
    }, [tema]);

    useEffect(() => {
        const alCambiar = (e) => setTema(e.detail);
        window.addEventListener(EVENTO, alCambiar);
        return () => window.removeEventListener(EVENTO, alCambiar);
    }, []);

    return [tema, setTema];
}
