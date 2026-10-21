import plugin from 'tailwindcss/plugin';
import defaultColors from 'tailwindcss/colors';

// Tema claro/oscuro sin tener que añadir variantes `dark:` en cada página.
//
// Las paletas usadas en la app se sirven desde variables CSS separadas por
// rol (texto, fondo, borde). En claro valen lo mismo que Tailwind; con la
// clase `.dark` en <html> se sustituyen: los fondos claros pasan a tonos
// oscuros, los textos oscuros a tonos claros y los bordes se atenúan. Los
// fondos sólidos (botones, puntos de estado) conservan su color.

const PALETAS = [
    'gray', 'indigo', 'red', 'green', 'amber', 'yellow', 'orange', 'blue',
    // Acentos de las secciones del menú y del inicio.
    'sky', 'teal', 'emerald', 'violet', 'rose', 'cyan', 'fuchsia',
];
const TONOS = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

const rgb = (hex) => {
    const n = parseInt(hex.slice(1), 16);
    return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`;
};

// Escala de grises en oscuro, igual para texto y bordes.
const GRIS_OSCURO = {
    50: '#263244', 100: '#111827', 200: '#374151', 300: '#4b5563', 400: '#6b7280',
    500: '#9ca3af', 600: '#b0b7c3', 700: '#d1d5db', 800: '#e5e7eb', 900: '#f3f4f6', 950: '#f9fafb',
};

// Fondos grises en oscuro. Del 400 en adelante son sólidos: el 500 sólo
// es el velo de los modales y el 700/800 el botón primario, que debe
// destacar sobre la superficie (#1f2937).
const GRIS_FONDO_OSCURO = {
    50: GRIS_OSCURO[50], 100: GRIS_OSCURO[100], 200: GRIS_OSCURO[200], 300: GRIS_OSCURO[300],
    500: '#030712', 700: '#6b7280', 800: '#4b5563',
};

// Tono de Tailwind que sustituye a cada tono en oscuro (paletas de color).
const ESPEJO = {
    texto: { 500: '400', 600: '400', 700: '300', 800: '200', 900: '100', 950: '100' },
    fondo: { 50: '950', 100: '900', 200: '800' },
    borde: { 100: '900', 200: '800', 300: '700' },
};

const ROLES = { texto: 'tx', fondo: 'bg', borde: 'bd' };

const oscuro = (rol, paleta, tono) => {
    if (paleta === 'gray') {
        if (rol === 'fondo') return GRIS_FONDO_OSCURO[tono] ?? null;
        return GRIS_OSCURO[tono];
    }
    const espejo = ESPEJO[rol][tono];
    return espejo ? defaultColors[paleta][espejo] : null;
};

const variable = (rol, paleta, tono) => `--${ROLES[rol]}-${paleta}-${tono}`;

const paletaPara = (rol) => Object.fromEntries(
    PALETAS.map((paleta) => [
        paleta,
        Object.fromEntries(TONOS.map((tono) => [tono, `rgb(var(${variable(rol, paleta, tono)}) / <alpha-value>)`])),
    ]),
);

const variables = (modo) => {
    const vars = {};
    for (const rol of Object.keys(ROLES)) {
        for (const paleta of PALETAS) {
            for (const tono of TONOS) {
                const hex = modo === 'claro' ? defaultColors[paleta][tono] : oscuro(rol, paleta, tono);
                if (hex) vars[variable(rol, paleta, tono)] = rgb(hex);
            }
        }
    }
    // Superficie de tarjetas, menús y modales (`bg-white`).
    vars['--bg-white'] = rgb(modo === 'claro' ? '#ffffff' : '#1f2937');
    return vars;
};

const campos = [
    "[type='text']", "[type='email']", "[type='url']", "[type='password']", "[type='number']",
    "[type='date']", "[type='datetime-local']", "[type='month']", "[type='search']", "[type='tel']",
    "[type='time']", "[type='week']", '[multiple]', 'textarea', 'select', "[type='checkbox']", "[type='radio']",
].join(', ');

export const temaClaroOscuro = {
    theme: {
        extend: {
            textColor: paletaPara('texto'),
            backgroundColor: {
                ...paletaPara('fondo'),
                white: 'rgb(var(--bg-white) / <alpha-value>)',
            },
            borderColor: paletaPara('borde'),
            ringColor: paletaPara('borde'),
        },
    },
    plugin: plugin(({ addBase }) => {
        addBase({
            ':root': variables('claro'),
            // Sólo en pantalla: al imprimir siempre se usa el tema claro.
            '@media screen': {
                '.dark': { ...variables('oscuro'), colorScheme: 'dark' },
                // @tailwindcss/forms fija los campos en blanco.
                [`:where(.dark) :is(${campos})`]: {
                    backgroundColor: 'rgb(var(--bg-white))',
                    color: 'rgb(var(--tx-gray-900))',
                },
            },
        });
    }),
};
