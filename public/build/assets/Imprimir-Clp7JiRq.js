import{r as x,j as o,H as g,L as u}from"./app-CfXuAsHx.js";const m={ancho:215.9,alto:279.4},s=20,t={folio:{top:22,left:150},fecha:{top:34,left:150},rfc:{top:41,left:118},cliente:{top:32,left:s,ancho:95},conceptos:{top:66,alto_renglon:6},cantidad:{left:s,ancho:12},descripcion:{left:s+16,ancho:100},precioUnitario:{right:s+28},importe:{right:s},subtotal:{top:232},iva:{top:239},total:{top:246},totalLetra:{top:242,left:s,ancho:120},pago:{top:256,left:s}},d=new Intl.NumberFormat("es-MX",{style:"currency",currency:"MXN"});function c(e){return`${e}mm`}const j=`
    @page {
        size: letter;
        margin: 0;
    }

    .nota-hoja {
        position: relative;
        width: ${c(m.ancho)};
        height: ${c(m.alto)};
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
`;function i({pos:e,className:a="",style:l={},children:r}){return o.jsx("div",{className:`nota-campo ${a}`,style:{...l,...b(e)},children:r})}function b({top:e,left:a,right:l,ancho:r}){return{top:e!==void 0?c(e):void 0,left:a!==void 0?c(a):void 0,right:l!==void 0?c(l):void 0,width:r!==void 0?c(r):void 0}}function N({nota:e}){x.useEffect(()=>{window.print()},[]);const a=e.cliente,l=e.conceptos??[],r=[a?.colonia&&`COL. ${a.colonia}`,a?.codigo_postal&&`C.P. ${a.codigo_postal}`].filter(Boolean).join(" ");return o.jsxs(o.Fragment,{children:[o.jsx(g,{title:`Nota de pago ${e.folio}`}),o.jsx("style",{children:j}),o.jsxs("div",{className:"no-imprimir flex items-center gap-4 p-4",children:[o.jsx(u,{href:route("notas-pago.index"),className:"text-sm text-indigo-600 hover:text-indigo-900",children:"← Volver a notas de pago"}),o.jsx("button",{type:"button",onClick:()=>window.print(),className:"rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover",children:"Imprimir"})]}),o.jsx("div",{className:"nota-fondo flex justify-center bg-gray-100 py-6",children:o.jsxs("div",{className:"nota-hoja border border-gray-300 shadow-sm",children:[o.jsx(i,{pos:t.folio,children:e.folio}),o.jsx(i,{pos:t.fecha,children:e.fecha}),o.jsx(i,{pos:t.rfc,children:a?.rfc}),o.jsxs(i,{pos:t.cliente,className:"nota-envolver",children:[o.jsx("p",{children:a?.nombre}),o.jsx("p",{children:a?.domicilio}),r&&o.jsx("p",{children:r}),o.jsx("p",{children:a?.poblacion})]}),l.map((n,h)=>{const p=t.conceptos.top+h*t.conceptos.alto_renglon,f=n.numero_caso?`${n.descripcion}, CASO: ${n.numero_caso}`:n.descripcion;return o.jsxs("div",{children:[o.jsx(i,{pos:{...t.cantidad,top:p},className:"nota-derecha",children:n.cantidad}),o.jsx(i,{pos:{...t.descripcion,top:p},style:{overflow:"hidden",textOverflow:"ellipsis"},children:f}),o.jsx(i,{pos:{...t.precioUnitario,top:p},className:"nota-derecha",children:d.format(n.precio_unitario)}),o.jsx(i,{pos:{...t.importe,top:p},className:"nota-derecha",children:d.format(n.importe)})]},n.id)}),o.jsx(i,{pos:{...t.importe,top:t.subtotal.top},className:"nota-derecha",children:d.format(e.subtotal)}),o.jsx(i,{pos:{...t.importe,top:t.iva.top},className:"nota-derecha",children:d.format(e.iva_monto)}),o.jsx(i,{pos:{...t.importe,top:t.total.top},className:"nota-derecha",children:d.format(e.total)}),o.jsxs(i,{pos:t.totalLetra,className:"nota-envolver",children:["(",e.total_letra,")"]}),o.jsxs(i,{pos:t.pago,className:"nota-pago",children:[o.jsx("span",{className:"nota-etiqueta",children:"Pagado"}),o.jsx("span",{className:"nota-recuadro",children:e.pagada?"Sí":"No"}),o.jsx("span",{className:"nota-etiqueta",style:{marginLeft:"8mm"},children:"Tipo de pago:"}),o.jsx("span",{children:e.tipo_pago})]})]})})]})}export{N as default};
