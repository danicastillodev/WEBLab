import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Medidas del rollo de la impresora de tickets. Si se cambia de rollo
 * (58 mm es el otro tamaño común), basta con ajustar estas dos.
 */
const ANCHO_PAPEL = '80mm';
const MARGEN_PAPEL = '3mm';

/**
 * El ticket no usa utilidades de Tailwind: al imprimirse en un rollo estrecho
 * las medidas tienen que ser exactas y en milímetros, así que lleva su propia
 * hoja de estilos y así el resultado no depende del tema de la aplicación.
 */
const ESTILOS = `
    @page {
        size: ${ANCHO_PAPEL} auto;
        margin: 0;
    }

    .ticket {
        width: ${ANCHO_PAPEL};
        box-sizing: border-box;
        padding: ${MARGEN_PAPEL};
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12pt;
        line-height: 1.25;
        color: #000;
        background: #fff;
    }

    .ticket p {
        margin: 0;
        overflow-wrap: break-word;
    }

    .ticket-negrita { font-weight: bold; }

    /* El encabezado del laboratorio va con los saltos de línea del formato
       F079; a 12 pt no caben en 80 mm, así que se reduce solo ese bloque. */
    .ticket-encabezado {
        text-align: center;
        font-size: 10pt;
    }

    /* Los separadores "======" del formato. Un borde doble ocupa siempre el
       ancho exacto del papel, sin depender del ancho de los caracteres. */
    .ticket-sep {
        border-top: 3px double #000;
        margin: 3mm 0;
    }

    .ticket-muestra { margin-top: 3mm; }

    .ticket-muestra + .ticket-muestra {
        border-top: 1px dashed #000;
        padding-top: 3mm;
    }

    /* Espacio en blanco para firmar y, debajo, la leyenda de la firma. */
    .ticket-firma {
        margin-top: 10mm;
        border-top: 1px solid #000;
        padding-top: 1mm;
    }

    /* Selector con la misma especificidad que ".ticket p" para que el
       margen no lo pise el reset de los párrafos de arriba. */
    .ticket p.ticket-aviso {
        margin-top: 8mm;
        font-weight: bold;
        text-transform: uppercase;
    }

    @media print {
        html, body {
            width: ${ANCHO_PAPEL};
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .no-imprimir { display: none !important; }

        .ticket-hoja {
            display: block;
            background: #fff;
            padding: 0;
        }

        .ticket {
            margin: 0;
            border: 0;
            box-shadow: none;
        }
    }
`;

/** "2026-08-25" (o un ISO completo) → "25/08/2026", sin desfase de zona horaria. */
function formatFecha(valor) {
    if (!valor) return '';
    const [y, m, d] = String(valor).slice(0, 10).split('-');
    return y && m && d ? `${d}/${m}/${y}` : String(valor);
}

export default function Ticket({ historia }) {
    useEffect(() => {
        window.print();
    }, []);

    const caso = String(historia.numero_caso).padStart(4, '0');
    const propietario = [historia.propietario?.nombre, historia.propietario?.apellidos]
        .filter(Boolean)
        .join(' ');
    const muestras = historia.muestras ?? [];

    return (
        <>
            <Head title={`Ticket #${caso}`} />
            <style>{ESTILOS}</style>

            <div className="no-imprimir flex items-center gap-4 p-4">
                <Link
                    href={route('historias-clinicas.index')}
                    className="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Volver a historias clínicas
                </Link>
                <button
                    type="button"
                    onClick={() => window.print()}
                    className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover"
                >
                    Imprimir
                </button>
            </div>

            <div className="ticket-hoja flex justify-center bg-gray-100 py-6">
                <div className="ticket border border-gray-300 shadow-sm">
                    {/* Encabezado fijo del laboratorio (formato F079) */}
                    <div className="ticket-encabezado">
                        <p className="ticket-negrita">DIAGNOSTICOS Y SERVICIOS</p>
                        <p className="ticket-negrita">INTEGRALES EN SANIDAD ANIMAL, S.C.</p>
                        <p>Laboratorio Regional de Patología de</p>
                        <p>El Salto, Jal.</p>
                        <p>Calz. Solidaridad Iberoamericana #7069</p>
                        <p>Delegación Las Pintas, El Salto Jal.</p>
                        <p>Formato: F079</p>
                    </div>

                    <div className="ticket-sep" />

                    <p className="ticket-negrita">Detalles de Historia Clínica</p>
                    <p>Caso: {caso}</p>
                    <p>Fecha Recepción: {formatFecha(historia.fecha_recepcion)}</p>
                    <p>Propietario:</p>
                    <p>{propietario}</p>

                    <div className="ticket-sep" />

                    <p className="ticket-negrita">Detalles de muestras</p>

                    {muestras.length === 0 ? (
                        <p className="ticket-muestra">Sin muestras registradas.</p>
                    ) : (
                        muestras.map((muestra, i) => (
                            <div className="ticket-muestra" key={muestra.id ?? i}>
                                <p>Análisis: {muestra.prueba?.nombre}</p>
                                <p>Cant: {muestra.cantidad}</p>
                                <p>Tipo: {muestra.tipo_muestra?.nombre}</p>
                                <p>Especie: {historia.especie?.nombre}</p>
                            </div>
                        ))
                    )}

                    <div className="ticket-sep" />

                    <div className="ticket-firma">
                        <p>Firma Propietario Entrega Muestras</p>
                    </div>
                    <div className="ticket-firma">
                        <p>Firma Laboratorio Recibe Muestras</p>
                    </div>
                    <div className="ticket-firma">
                        <p>Firma Recibe Área Técnica</p>
                    </div>

                    <p className="ticket-aviso">
                        Sin excepción de persona para la entrega del resultado será necesario
                        presentar este ticket.
                    </p>
                </div>
            </div>
        </>
    );
}
