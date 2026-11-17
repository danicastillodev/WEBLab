import { Head, Link } from '@inertiajs/react';

/**
 * Hoja de resultados (formato F070), tamaño carta. Como el ticket, lleva su
 * propia hoja de estilos en lugar de Tailwind: las medidas son de papel y el
 * resultado no debe depender del tema (claro u oscuro) de la aplicación.
 */
const ESTILOS = `
    @page {
        size: letter;
        margin: 10mm;
    }

    .reporte-fondo {
        background: #f3f4f6;
        padding: 24px 0;
    }

    .reporte {
        width: 196mm;
        min-height: 259mm;
        margin: 0 auto 24px;
        padding: 8mm;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        background: #fff;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8.5pt;
        line-height: 1.3;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    }

    .reporte p { margin: 0; }

    .reporte-encabezado {
        display: flex;
        align-items: center;
        gap: 4mm;
    }

    .reporte-logo { width: 18mm; height: auto; }

    .reporte-razon {
        font-size: 15pt;
        white-space: nowrap;
        font-style: italic;
        color: #1e3a8a;
        text-align: center;
    }

    .reporte-laboratorio {
        font-weight: bold;
        text-align: center;
        font-size: 8pt;
    }

    .reporte-titulo {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: end;
        margin-top: 3mm;
        padding-bottom: 1mm;
        border-bottom: 1.5px solid #1e3a8a;
        font-weight: bold;
    }

    .reporte-titulo span:nth-child(2) { font-size: 9pt; }

    .reporte-caja {
        border: 1px solid #000;
        padding: 2mm 3mm;
        margin-top: 2mm;
    }

    .reporte-fila {
        display: flex;
        gap: 4mm;
    }

    .reporte-fila > * { flex: 1; }

    .reporte-etiqueta {
        font-weight: bold;
        color: #1e3a8a;
        margin-right: 1.5mm;
    }

    .reporte-caso {
        font-size: 13pt;
        font-weight: bold;
    }

    .reporte-fechas { text-align: right; }

    .reporte-normas {
        margin-top: 2mm;
        font-size: 7.5pt;
        text-transform: uppercase;
    }

    .reporte-resultado {
        flex: 1;
        min-height: 45mm;
    }

    .reporte-resultado-texto {
        margin-top: 1mm;
        text-transform: uppercase;
    }

    .reporte-resultado-texto ul,
    .reporte-resultado-texto ol { margin: 0; padding-left: 5mm; }

    .reporte-firmas {
        display: grid;
        grid-template-columns: auto 1fr 1fr;
        gap: 6mm;
        align-items: end;
        min-height: 32mm;
    }

    .reporte-firma {
        border-top: 1px solid #000;
        padding-top: 1mm;
        text-align: center;
        text-transform: uppercase;
    }

    .reporte-firma strong { display: block; }

    .reporte-pie {
        display: flex;
        justify-content: space-between;
        gap: 6mm;
        margin-top: 2mm;
        font-size: 7pt;
    }

    .reporte-pie-datos {
        display: grid;
        grid-template-columns: auto auto;
        column-gap: 4mm;
        white-space: nowrap;
    }

    @media print {
        html, body { background: #fff; }

        .no-imprimir { display: none !important; }

        .reporte-fondo { background: #fff; padding: 0; }

        .reporte {
            width: auto;
            min-height: 257mm;
            margin: 0;
            padding: 0;
            box-shadow: none;
            break-after: page;
        }

        .reporte:last-child { break-after: auto; }
    }
`;

const LEYENDA = 'Prohibida la reproducción total o parcial de este documento sin el permiso por escrito de los encargados del Laboratorio. '
    + 'No es válido sin las firmas y sello oficial. El presente ampara sólo las muestras sometidas a los análisis mencionados.';

/** "2026-08-27..." → "27/08/2026". */
function fecha(valor) {
    if (!valor) return '';
    const [y, m, d] = String(valor).slice(0, 10).split('-');
    return y && m && d ? `${d}/${m}/${y}` : String(valor);
}

function edad(historia) {
    if (!historia.edad_unidad || ['NR', 'NA'].includes(historia.edad_unidad)) return 'N/R';
    return [historia.edad_valor, historia.edad_unidad].filter(Boolean).join(' ');
}

const nombreCompleto = (p) => [p?.nombre, p?.apellidos].filter(Boolean).join(' ');

function Dato({ etiqueta, children }) {
    return (
        <p>
            <span className="reporte-etiqueta">{etiqueta}:</span>
            {children}
        </p>
    );
}

/** Sin texto capturado, el resultado se arma con las muestras trabajadas. */
function Resultado({ diagnostico }) {
    if (diagnostico.resultado) {
        return <div className="reporte-resultado-texto" dangerouslySetInnerHTML={{ __html: diagnostico.resultado }} />;
    }

    return (
        <div className="reporte-resultado-texto">
            {(diagnostico.muestras ?? []).map((m) => (
                <p key={m.id}>
                    {m.cantidad} {m.identificacion}: {m.resultado}
                    {m.aretes ? ` (ARETES: ${m.aretes})` : ''}
                </p>
            ))}
        </div>
    );
}

function Hoja({ historia, muestra, numero, total, datosGenerales, usuario, fechaImpresion }) {
    const diagnostico = muestra.diagnostico;
    const servicio = muestra.servicio ?? {};
    const caso = [historia.numero_caso, servicio.siglas].filter(Boolean).join('-');

    // "Realizado en": cuántas muestras se trabajaron y de qué tipo.
    const trabajadas = (diagnostico.muestras ?? []).reduce((suma, m) => suma + Number(m.cantidad || 0), 0) || muestra.cantidad;
    const realizadoEn = [trabajadas, muestra.tipo_muestra?.nombre].filter(Boolean).join(' ');

    const direccion = historia.direccion;
    const calle = direccion
        ? [`${direccion.calle} ${direccion.numero_exterior ?? ''}`.trim(), direccion.colonia].filter(Boolean).join(' ')
        : '';
    const explotacion = historia.explotacion;
    const lugarExplotacion = explotacion
        ? [explotacion.nombre, explotacion.direccion, explotacion.municipio?.nombre, explotacion.estado?.nombre].filter(Boolean).join(', ')
        : '';

    const laboratorio = datosGenerales;
    const domicilio = laboratorio
        ? [laboratorio.direccion, laboratorio.colonia, laboratorio.municipio?.nombre, laboratorio.estado?.nombre].filter(Boolean).join(', ')
        : '';

    return (
        <div className="reporte">
            <div className="reporte-encabezado">
                <img src="/images/logo.png" alt="Logo" className="reporte-logo" />
                <div style={{ flex: 1 }}>
                    <p className="reporte-razon">{laboratorio?.razon_social}</p>
                    <p className="reporte-laboratorio">{laboratorio?.nombre_laboratorio}</p>
                </div>
            </div>

            <div className="reporte-titulo">
                <span>F070</span>
                <span>Reporte de Resultados</span>
                <span />
            </div>

            <div className="reporte-caja">
                <div className="reporte-fila">
                    <p className="reporte-caso"><span className="reporte-etiqueta">Caso:</span>{caso}</p>
                    <div className="reporte-fechas">
                        <Dato etiqueta="Fecha Recepción">{fecha(historia.fecha_recepcion)}</Dato>
                        <Dato etiqueta="Fecha Realización">{fecha(diagnostico.fecha_realizacion)}</Dato>
                        <Dato etiqueta="Fecha Emisión">{fecha(diagnostico.fecha_emision)}</Dato>
                    </div>
                </div>
                <div style={{ marginTop: '2mm' }}>
                    <Dato etiqueta="Propietario">{nombreCompleto(historia.propietario)}</Dato>
                    <Dato etiqueta="Dirección">{calle}</Dato>
                    <div className="reporte-fila">
                        <Dato etiqueta="Municipio">{direccion?.municipio?.nombre}</Dato>
                        <Dato etiqueta="Estado">{direccion?.estado?.nombre}</Dato>
                    </div>
                </div>
            </div>

            <div className="reporte-caja">
                <div className="reporte-fila">
                    <Dato etiqueta="Especie">{historia.especie?.nombre}</Dato>
                    <Dato etiqueta="Raza">{historia.raza?.nombre}</Dato>
                    <Dato etiqueta="Sexo">{historia.sexo}</Dato>
                    <Dato etiqueta="Edad">{edad(historia)}</Dato>
                </div>
                <div className="reporte-fila" style={{ marginTop: '1mm' }}>
                    <Dato etiqueta="Análisis">{servicio.descripcion}</Dato>
                    <Dato etiqueta="Realizado en">{realizadoEn}</Dato>
                </div>
                <div style={{ marginTop: '2mm' }}>
                    <Dato etiqueta="Método">{servicio.metodo?.nombre}</Dato>
                </div>
            </div>

            {(servicio.normas ?? []).length > 0 && (
                <div className="reporte-normas">
                    {servicio.normas.map((norma) => (
                        <p key={norma.id}>{norma.descripcion || norma.nombre}</p>
                    ))}
                </div>
            )}

            <div className="reporte-caja reporte-resultado">
                <p className="reporte-etiqueta">Resultado:</p>
                <Resultado diagnostico={diagnostico} />
            </div>

            <div className="reporte-caja reporte-firmas">
                <p className="reporte-etiqueta" style={{ alignSelf: 'center' }}>Observaciones:</p>
                <div className="reporte-firma">
                    {servicio.analista?.nombre}
                    <strong>Analista</strong>
                </div>
                <div className="reporte-firma">
                    {laboratorio?.jefe_laboratorio}
                    <strong>Jefe de Laboratorio</strong>
                </div>
            </div>

            <div className="reporte-caja">
                <Dato etiqueta="Comentarios">{muestra.notas}</Dato>
            </div>

            <div className="reporte-caja">
                <div className="reporte-fila">
                    <div style={{ flex: 2 }}>
                        <Dato etiqueta="Explotación">{lugarExplotacion}</Dato>
                        <Dato etiqueta="Función Zootécnica">{historia.funcionZootecnica?.nombre}</Dato>
                    </div>
                    <Dato etiqueta="Cantidad de Animales">{historia.animales_explotacion ?? 'N/R'}</Dato>
                </div>
            </div>

            <div className="reporte-pie">
                <div>
                    <p>{LEYENDA}</p>
                    <p style={{ fontWeight: 'bold' }}>El presente resultado invalida impresiones anteriores del mismo.</p>
                    {domicilio && <p style={{ marginTop: '2mm' }}>{domicilio}</p>}
                </div>
                <div className="reporte-pie-datos">
                    <span>Fecha Impresión: {fechaImpresion}</span>
                    <span />
                    <span>Num. Impresión: {diagnostico.impresiones}</span>
                    <span>Diag. {numero} de {total}</span>
                    <span>Usuario: {usuario}</span>
                    <span>Pág. {numero} de {total}</span>
                    {laboratorio?.telefono && <span>Tel: {laboratorio.telefono}</span>}
                    <span />
                    {laboratorio?.email && <span>E-mail: {laboratorio.email}</span>}
                </div>
            </div>
        </div>
    );
}

export default function Resultados({ historia, analisis, datosGenerales, usuario, fechaImpresion }) {
    return (
        <>
            <Head title={`Resultados caso ${historia.numero_caso}`} />
            <style>{ESTILOS}</style>

            <div className="no-imprimir flex items-center gap-4 p-4">
                <Link
                    href={route('historias-clinicas.show', historia.id)}
                    className="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Volver a la historia clínica
                </Link>
                <button
                    type="button"
                    onClick={() => window.print()}
                    className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover"
                >
                    Imprimir
                </button>
            </div>

            <div className="reporte-fondo">
                {analisis.map((muestra, i) => (
                    <Hoja
                        key={muestra.id}
                        historia={historia}
                        muestra={muestra}
                        numero={i + 1}
                        total={analisis.length}
                        datosGenerales={datosGenerales}
                        usuario={usuario}
                        fechaImpresion={fechaImpresion}
                    />
                ))}
            </div>
        </>
    );
}
