import{r as o,j as e,H as d,L as l}from"./app-CelsZeRw.js";const n="80mm",p="3mm",m=`
    @page {
        size: ${n} auto;
        margin: 0;
    }

    .ticket {
        width: ${n};
        box-sizing: border-box;
        padding: ${p};
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
            width: ${n};
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
`;function x(a){if(!a)return"";const[i,r,t]=String(a).slice(0,10).split("-");return i&&r&&t?`${t}/${r}/${i}`:String(a)}function b({historia:a}){o.useEffect(()=>{window.print()},[]);const i=String(a.numero_caso).padStart(4,"0"),r=[a.propietario?.nombre,a.propietario?.apellidos].filter(Boolean).join(" "),t=a.muestras??[];return e.jsxs(e.Fragment,{children:[e.jsx(d,{title:`Ticket #${i}`}),e.jsx("style",{children:m}),e.jsxs("div",{className:"no-imprimir flex items-center gap-4 p-4",children:[e.jsx(l,{href:route("historias-clinicas.index"),className:"text-sm text-indigo-600 hover:text-indigo-900",children:"← Volver a historias clínicas"}),e.jsx("button",{type:"button",onClick:()=>window.print(),className:"rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover",children:"Imprimir"})]}),e.jsx("div",{className:"ticket-hoja flex justify-center bg-gray-100 py-6",children:e.jsxs("div",{className:"ticket border border-gray-300 shadow-sm",children:[e.jsxs("div",{className:"ticket-encabezado",children:[e.jsx("p",{className:"ticket-negrita",children:"DIAGNOSTICOS Y SERVICIOS"}),e.jsx("p",{className:"ticket-negrita",children:"INTEGRALES EN SANIDAD ANIMAL, S.C."}),e.jsx("p",{children:"Laboratorio Regional de Patología de"}),e.jsx("p",{children:"El Salto, Jal."}),e.jsx("p",{children:"Calz. Solidaridad Iberoamericana #7069"}),e.jsx("p",{children:"Delegación Las Pintas, El Salto Jal."}),e.jsx("p",{children:"Formato: F079"})]}),e.jsx("div",{className:"ticket-sep"}),e.jsx("p",{className:"ticket-negrita",children:"Detalles de Historia Clínica"}),e.jsxs("p",{children:["Caso: ",i]}),e.jsxs("p",{children:["Fecha Recepción: ",x(a.fecha_recepcion)]}),e.jsx("p",{children:"Propietario:"}),e.jsx("p",{children:r}),e.jsx("div",{className:"ticket-sep"}),e.jsx("p",{className:"ticket-negrita",children:"Detalles de muestras"}),t.length===0?e.jsx("p",{className:"ticket-muestra",children:"Sin muestras registradas."}):t.map((s,c)=>e.jsxs("div",{className:"ticket-muestra",children:[e.jsxs("p",{children:["Análisis: ",s.prueba?.nombre]}),e.jsxs("p",{children:["Cant: ",s.cantidad]}),e.jsxs("p",{children:["Tipo: ",s.tipo_muestra?.nombre]}),e.jsxs("p",{children:["Especie: ",a.especie?.nombre]})]},s.id??c)),e.jsx("div",{className:"ticket-sep"}),e.jsx("div",{className:"ticket-firma",children:e.jsx("p",{children:"Firma Propietario Entrega Muestras"})}),e.jsx("div",{className:"ticket-firma",children:e.jsx("p",{children:"Firma Laboratorio Recibe Muestras"})}),e.jsx("div",{className:"ticket-firma",children:e.jsx("p",{children:"Firma Recibe Área Técnica"})}),e.jsx("p",{className:"ticket-aviso",children:"Sin excepción de persona para la entrega del resultado será necesario presentar este ticket."})]})})]})}export{b as default};
