import{r as f,j as o,H as x,L as g}from"./app-BsqQHZl1.js";const p={ancho:215.9,alto:279.4},e={folio:{top:22,left:150},fecha:{top:34,left:150},tipoPago:{top:50,left:155},rfc:{top:41,left:118},cliente:{top:32,left:8,ancho:105},conceptos:{top:66,alto_renglon:6},cantidad:{left:8,ancho:12},descripcion:{left:24,ancho:110},precioUnitario:{right:50},importe:{right:22},subtotal:{top:232},iva:{top:239},total:{top:246},totalLetra:{top:242,left:12,ancho:140}},l=new Intl.NumberFormat("es-MX",{style:"currency",currency:"MXN"});function c(t){return`${t}mm`}const j=`
    @page {
        size: letter;
        margin: 0;
    }

    .nota-hoja {
        position: relative;
        width: ${c(p.ancho)};
        height: ${c(p.alto)};
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
`;function a({pos:t,className:i="",style:s={},children:n}){return o.jsx("div",{className:`nota-campo ${i}`,style:{...s,...u(t)},children:n})}function u({top:t,left:i,right:s,ancho:n}){return{top:t!==void 0?c(t):void 0,left:i!==void 0?c(i):void 0,right:s!==void 0?c(s):void 0,width:n!==void 0?c(n):void 0}}function b({nota:t}){f.useEffect(()=>{window.print()},[]);const i=t.cliente,s=t.conceptos??[],n=[i?.colonia&&`COL. ${i.colonia}`,i?.codigo_postal&&`C.P. ${i.codigo_postal}`].filter(Boolean).join(" ");return o.jsxs(o.Fragment,{children:[o.jsx(x,{title:`Nota de pago ${t.folio}`}),o.jsx("style",{children:j}),o.jsxs("div",{className:"no-imprimir flex items-center gap-4 p-4",children:[o.jsx(g,{href:route("notas-pago.index"),className:"text-sm text-indigo-600 hover:text-indigo-900",children:"← Volver a notas de pago"}),o.jsx("button",{type:"button",onClick:()=>window.print(),className:"rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover",children:"Imprimir"})]}),o.jsx("div",{className:"nota-fondo flex justify-center bg-gray-100 py-6",children:o.jsxs("div",{className:"nota-hoja border border-gray-300 shadow-sm",children:[o.jsx(a,{pos:e.folio,children:t.folio}),o.jsx(a,{pos:e.fecha,children:t.fecha}),o.jsx(a,{pos:e.rfc,children:i?.rfc}),o.jsx(a,{pos:e.tipoPago,children:t.tipo_pago}),o.jsxs(a,{pos:e.cliente,className:"nota-envolver",children:[o.jsx("p",{children:i?.nombre}),o.jsx("p",{children:i?.domicilio}),n&&o.jsx("p",{children:n}),o.jsx("p",{children:i?.poblacion})]}),s.map((r,m)=>{const d=e.conceptos.top+m*e.conceptos.alto_renglon,h=r.numero_caso?`${r.descripcion}, CASO: ${r.numero_caso}`:r.descripcion;return o.jsxs("div",{children:[o.jsx(a,{pos:{...e.cantidad,top:d},className:"nota-derecha",children:r.cantidad}),o.jsx(a,{pos:{...e.descripcion,top:d},style:{overflow:"hidden",textOverflow:"ellipsis"},children:h}),o.jsx(a,{pos:{...e.precioUnitario,top:d},className:"nota-derecha",children:l.format(r.precio_unitario)}),o.jsx(a,{pos:{...e.importe,top:d},className:"nota-derecha",children:l.format(r.importe)})]},r.id)}),o.jsx(a,{pos:{...e.importe,top:e.subtotal.top},className:"nota-derecha",children:l.format(t.subtotal)}),o.jsx(a,{pos:{...e.importe,top:e.iva.top},className:"nota-derecha",children:l.format(t.iva_monto)}),o.jsx(a,{pos:{...e.importe,top:e.total.top},className:"nota-derecha",children:l.format(t.total)}),o.jsxs(a,{pos:e.totalLetra,className:"nota-envolver",children:["(",t.total_letra,")"]})]})})]})}export{b as default};
