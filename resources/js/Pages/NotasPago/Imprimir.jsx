import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * La nota se imprime en una hoja carta: arriba los datos del cliente y los
 * conceptos, al pie el subtotal, el IVA y el total con número y con letra.
 *
 * Todas las posiciones están en milímetros medidos desde la esquina superior
 * izquierda de la hoja. Si algún dato no cae en su lugar sobre el papel,
 * basta con ajustar aquí la coordenada correspondiente.
 */
const HOJA = { ancho: 215.9, alto: 279.4 };

// Margen lateral: nada se imprime a menos de esto de los bordes izquierdo y
// derecho de la hoja.
const MARGEN = 20;

const POS = {
    folio: { top: 22, left: 150 },
    fecha: { top: 34, left: 150 },
    rfc: { top: 41, left: 118 },
    cliente: { top: 32, left: MARGEN, ancho: 95 },
    // Renglones de conceptos: la columna de importes se alinea a la derecha.
    conceptos: { top: 66, alto_renglon: 6 },
    cantidad: { left: MARGEN, ancho: 12 },
    descripcion: { left: MARGEN + 16, ancho: 100 },
    precioUnitario: { right: MARGEN + 28 },
    importe: { right: MARGEN },
    // Pie de la hoja.
    subtotal: { top: 232 },
    iva: { top: 239 },
    total: { top: 246 },
    totalLetra: { top: 242, left: MARGEN, ancho: 120 },
    pago: { top: 256, left: MARGEN },
};

const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

function mm(valor) {
    return `${valor}mm`;
}

/**
 * Igual que el ticket, el formato lleva su propia hoja de estilos en mm para
 * no depender de Tailwind ni del tema de la aplicación al imprimirse.
 */
const ESTILOS = `
    @page {
        size: letter;
        margin: 0;
    }

    .nota-hoja {
        position: relative;
        width: ${mm(HOJA.ancho)};
        height: ${mm(HOJA.alto)};
        box-sizing: border-box;
        overflow: hidden;
        font-family: 'Arial Narrow', 'Roboto Condensed', 'Helvetica Neue', Arial, sans-serif;
        font-stretch: condensed;
        font-size: 10pt;
        line-height: 1.3;
        text-transform: uppercase;
        color: #000;
        background: #fff;
    }

    .nota-hoja p {
        margin: 0;
    }

    .nota-campo {
        position: absolute;
        white-space: nowrap;
    }

    .nota-envolver {
        white-space: normal;
        overflow-wrap: break-word;
    }

    .nota-derecha {
        text-align: right;
    }

    .nota-pago {
        display: flex;
        align-items: center;
        gap: 3mm;
    }

    .nota-etiqueta {
        font-weight: bold;
    }

    .nota-recuadro {
        display: inline-block;
        min-width: 10mm;
        padding: 1mm 2mm;
        border: 0.3mm solid #000;
        text-align: center;
        font-weight: bold;
    }

    @media print {
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .no-imprimir { display: none !important; }

        .nota-fondo {
            display: block;
            padding: 0;
            background: #fff;
        }

        .nota-hoja {
            border: 0;
            box-shadow: none;
        }
    }
`;

function Campo({ pos, className = '', style = {}, children }) {
    return (
        <div className={`nota-campo ${className}`} style={{ ...style, ...posicion(pos) }}>
            {children}
        </div>
    );
}

function posicion({ top, left, right, ancho }) {
    return {
        top: top !== undefined ? mm(top) : undefined,
        left: left !== undefined ? mm(left) : undefined,
        right: right !== undefined ? mm(right) : undefined,
        width: ancho !== undefined ? mm(ancho) : undefined,
    };
}

export default function Imprimir({ nota }) {
    useEffect(() => {
        window.print();
    }, []);

    const cliente = nota.cliente;
    const conceptos = nota.conceptos ?? [];
    const colonia = [cliente?.colonia && `COL. ${cliente.colonia}`, cliente?.codigo_postal && `C.P. ${cliente.codigo_postal}`]
        .filter(Boolean)
        .join(' ');

    return (
        <>
            <Head title={`Nota de pago ${nota.folio}`} />
            <style>{ESTILOS}</style>

            <div className="no-imprimir flex items-center gap-4 p-4">
                <Link href={route('notas-pago.index')} className="text-sm text-indigo-600 hover:text-indigo-900">
                    ← Volver a notas de pago
                </Link>
                <button
                    type="button"
                    onClick={() => window.print()}
                    className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover"
                >
                    Imprimir
                </button>
            </div>

            <div className="nota-fondo flex justify-center bg-gray-100 py-6">
                <div className="nota-hoja border border-gray-300 shadow-sm">
                    {/* Encabezado: cliente a la izquierda, datos de la nota a la derecha */}
                    <Campo pos={POS.folio}>{nota.folio}</Campo>
                    <Campo pos={POS.fecha}>{nota.fecha}</Campo>
                    <Campo pos={POS.rfc}>{cliente?.rfc}</Campo>

                    <Campo pos={POS.cliente} className="nota-envolver">
                        <p>{cliente?.nombre}</p>
                        <p>{cliente?.domicilio}</p>
                        {colonia && <p>{colonia}</p>}
                        <p>{cliente?.poblacion}</p>
                    </Campo>

                    {/* Conceptos */}
                    {conceptos.map((concepto, i) => {
                        const top = POS.conceptos.top + i * POS.conceptos.alto_renglon;
                        const descripcion = concepto.numero_caso
                            ? `${concepto.descripcion}, CASO: ${concepto.numero_caso}`
                            : concepto.descripcion;

                        return (
                            <div key={concepto.id}>
                                <Campo pos={{ ...POS.cantidad, top }} className="nota-derecha">
                                    {concepto.cantidad}
                                </Campo>
                                <Campo pos={{ ...POS.descripcion, top }} style={{ overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                    {descripcion}
                                </Campo>
                                <Campo pos={{ ...POS.precioUnitario, top }} className="nota-derecha">
                                    {moneda.format(concepto.precio_unitario)}
                                </Campo>
                                <Campo pos={{ ...POS.importe, top }} className="nota-derecha">
                                    {moneda.format(concepto.importe)}
                                </Campo>
                            </div>
                        );
                    })}

                    {/* Pie: totales con número y con letra */}
                    <Campo pos={{ ...POS.importe, top: POS.subtotal.top }} className="nota-derecha">
                        {moneda.format(nota.subtotal)}
                    </Campo>
                    <Campo pos={{ ...POS.importe, top: POS.iva.top }} className="nota-derecha">
                        {moneda.format(nota.iva_monto)}
                    </Campo>
                    <Campo pos={{ ...POS.importe, top: POS.total.top }} className="nota-derecha">
                        {moneda.format(nota.total)}
                    </Campo>
                    <Campo pos={POS.totalLetra} className="nota-envolver">
                        ({nota.total_letra})
                    </Campo>

                    {/* Pie: si ya se pagó y cómo */}
                    <Campo pos={POS.pago} className="nota-pago">
                        <span className="nota-recuadro">{nota.pagada ? 'Sí' : 'No'}</span>
                        <span className="nota-etiqueta">Pagado</span>
                        <span className="nota-etiqueta" style={{ marginLeft: '8mm' }}>Tipo de pago:</span>
                        <span>{nota.tipo_pago}</span>
                    </Campo>
                </div>
            </div>
        </>
    );
}
