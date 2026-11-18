import{j as e,H as y,L as q}from"./app-ooW4efK2.js";const z=`
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
`,_="Prohibida la reproducción total o parcial de este documento sin el permiso por escrito de los encargados del Laboratorio. No es válido sin las firmas y sello oficial. El presente ampara sólo las muestras sometidas a los análisis mencionados.";function u(r){if(!r)return"";const[i,t,s]=String(r).slice(0,10).split("-");return i&&t&&s?`${s}/${t}/${i}`:String(r)}function w(r){return!r.edad_unidad||["NR","NA"].includes(r.edad_unidad)?"N/R":[r.edad_valor,r.edad_unidad].filter(Boolean).join(" ")}const E=r=>[r?.nombre,r?.apellidos].filter(Boolean).join(" ");function a({etiqueta:r,children:i}){return e.jsxs("p",{children:[e.jsxs("span",{className:"reporte-etiqueta",children:[r,":"]}),i]})}function R({diagnostico:r}){return r.resultado?e.jsx("div",{className:"reporte-resultado-texto",dangerouslySetInnerHTML:{__html:r.resultado}}):e.jsx("div",{className:"reporte-resultado-texto",children:(r.muestras??[]).map(i=>e.jsxs("p",{children:[i.cantidad," ",i.identificacion,": ",i.resultado,i.aretes?` (ARETES: ${i.aretes})`:""]},i.id))})}function k({historia:r,muestra:i,numero:t,total:s,datosGenerales:x,usuario:m,fechaImpresion:h}){const d=i.diagnostico,n=i.servicio??{},f=[r.numero_caso,n.siglas].filter(Boolean).join("-"),g=[(d.muestras??[]).reduce((p,v)=>p+Number(v.cantidad||0),0)||i.cantidad,i.tipo_muestra?.nombre].filter(Boolean).join(" "),l=r.direccion,b=l?[`${l.calle} ${l.numero_exterior??""}`.trim(),l.colonia].filter(Boolean).join(" "):"",c=r.explotacion,N=c?[c.nombre,c.direccion,c.municipio?.nombre,c.estado?.nombre].filter(Boolean).join(", "):"",o=x,j=o?[o.direccion,o.colonia,o.municipio?.nombre,o.estado?.nombre].filter(Boolean).join(", "):"";return e.jsxs("div",{className:"reporte",children:[e.jsxs("div",{className:"reporte-encabezado",children:[e.jsx("img",{src:"/images/logo.png",alt:"Logo",className:"reporte-logo"}),e.jsxs("div",{style:{flex:1},children:[e.jsx("p",{className:"reporte-razon",children:o?.razon_social}),e.jsx("p",{className:"reporte-laboratorio",children:o?.nombre_laboratorio})]})]}),e.jsxs("div",{className:"reporte-titulo",children:[e.jsx("span",{children:"F070"}),e.jsx("span",{children:"Reporte de Resultados"}),e.jsx("span",{})]}),e.jsxs("div",{className:"reporte-caja",children:[e.jsxs("div",{className:"reporte-fila",children:[e.jsxs("p",{className:"reporte-caso",children:[e.jsx("span",{className:"reporte-etiqueta",children:"Caso:"}),f]}),e.jsxs("div",{className:"reporte-fechas",children:[e.jsx(a,{etiqueta:"Fecha Recepción",children:u(r.fecha_recepcion)}),e.jsx(a,{etiqueta:"Fecha Realización",children:u(d.fecha_realizacion)}),e.jsx(a,{etiqueta:"Fecha Emisión",children:u(d.fecha_emision)})]})]}),e.jsxs("div",{style:{marginTop:"2mm"},children:[e.jsx(a,{etiqueta:"Propietario",children:E(r.propietario)}),e.jsx(a,{etiqueta:"Dirección",children:b}),e.jsxs("div",{className:"reporte-fila",children:[e.jsx(a,{etiqueta:"Municipio",children:l?.municipio?.nombre}),e.jsx(a,{etiqueta:"Estado",children:l?.estado?.nombre})]})]})]}),e.jsxs("div",{className:"reporte-caja",children:[e.jsxs("div",{className:"reporte-fila",children:[e.jsx(a,{etiqueta:"Especie",children:r.especie?.nombre}),e.jsx(a,{etiqueta:"Raza",children:r.raza?.nombre}),e.jsx(a,{etiqueta:"Sexo",children:r.sexo}),e.jsx(a,{etiqueta:"Edad",children:w(r)})]}),e.jsxs("div",{className:"reporte-fila",style:{marginTop:"1mm"},children:[e.jsx(a,{etiqueta:"Análisis",children:n.descripcion}),e.jsx(a,{etiqueta:"Realizado en",children:g})]}),e.jsx("div",{style:{marginTop:"2mm"},children:e.jsx(a,{etiqueta:"Método",children:n.metodo?.nombre})})]}),(n.normas??[]).length>0&&e.jsx("div",{className:"reporte-normas",children:n.normas.map(p=>e.jsx("p",{children:p.descripcion||p.nombre},p.id))}),e.jsxs("div",{className:"reporte-caja reporte-resultado",children:[e.jsx("p",{className:"reporte-etiqueta",children:"Resultado:"}),e.jsx(R,{diagnostico:d})]}),e.jsxs("div",{className:"reporte-caja reporte-firmas",children:[e.jsx("p",{className:"reporte-etiqueta",style:{alignSelf:"center"},children:"Observaciones:"}),e.jsxs("div",{className:"reporte-firma",children:[n.analista?.nombre,e.jsx("strong",{children:"Analista"})]}),e.jsxs("div",{className:"reporte-firma",children:[o?.jefe_laboratorio,e.jsx("strong",{children:"Jefe de Laboratorio"})]})]}),e.jsx("div",{className:"reporte-caja",children:e.jsx(a,{etiqueta:"Comentarios",children:i.notas})}),e.jsx("div",{className:"reporte-caja",children:e.jsxs("div",{className:"reporte-fila",children:[e.jsxs("div",{style:{flex:2},children:[e.jsx(a,{etiqueta:"Explotación",children:N}),e.jsx(a,{etiqueta:"Función Zootécnica",children:r.funcionZootecnica?.nombre})]}),e.jsx(a,{etiqueta:"Cantidad de Animales",children:r.animales_explotacion??"N/R"})]})}),e.jsxs("div",{className:"reporte-pie",children:[e.jsxs("div",{children:[e.jsx("p",{children:_}),e.jsx("p",{style:{fontWeight:"bold"},children:"El presente resultado invalida impresiones anteriores del mismo."}),j&&e.jsx("p",{style:{marginTop:"2mm"},children:j})]}),e.jsxs("div",{className:"reporte-pie-datos",children:[e.jsxs("span",{children:["Fecha Impresión: ",h]}),e.jsx("span",{}),e.jsxs("span",{children:["Num. Impresión: ",d.impresiones]}),e.jsxs("span",{children:["Diag. ",t," de ",s]}),e.jsxs("span",{children:["Usuario: ",m]}),e.jsxs("span",{children:["Pág. ",t," de ",s]}),o?.telefono&&e.jsxs("span",{children:["Tel: ",o.telefono]}),e.jsx("span",{}),o?.email&&e.jsxs("span",{children:["E-mail: ",o.email]})]})]})]})}function T({historia:r,analisis:i,datosGenerales:t,usuario:s,fechaImpresion:x}){return e.jsxs(e.Fragment,{children:[e.jsx(y,{title:`Resultados caso ${r.numero_caso}`}),e.jsx("style",{children:z}),e.jsxs("div",{className:"no-imprimir flex items-center gap-4 p-4",children:[e.jsx(q,{href:route("historias-clinicas.show",r.id),className:"text-sm text-indigo-600 hover:text-indigo-900",children:"← Volver a la historia clínica"}),e.jsx("button",{type:"button",onClick:()=>window.print(),className:"rounded-md bg-brand px-4 py-2 text-sm font-medium text-white hover:bg-brand-hover",children:"Imprimir"})]}),e.jsx("div",{className:"reporte-fondo",children:i.map((m,h)=>e.jsx(k,{historia:r,muestra:m,numero:h+1,total:i.length,datosGenerales:t,usuario:s,fechaImpresion:x},m.id))})]})}export{T as default};
