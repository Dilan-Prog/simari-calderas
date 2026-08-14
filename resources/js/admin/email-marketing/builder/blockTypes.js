/**
 * blockTypes.js
 *
 * Catálogo de bloques del armador visual (vista sencilla) de Plantillas.
 * Portado literal desde el mockup `Email Marketing.dc.html` (líneas
 * ~897-934, ~1041-1051, ~1070-1076), con UNA corrección obligatoria: los
 * tokens dinámicos. El resolutor real (`app/Services/EmailTemplateService.php
 * ::render()`, verificado línea por línea) solo soporta el catálogo de
 * abajo (`TOKENS`) — en inglés, sin `{{contacto.*}}`/`{{negocio.*}}`/
 * `{{empresa.nombre}}` del mockup (esos no tienen resolutor y saldrían
 * literales, sin reemplazar, en un envío real). Todo texto de ejemplo que
 * el mockup escribía con esos tokens en español se reescribió aquí para
 * usar únicamente el catálogo real, o se volvió texto estático cuando no
 * había token real equivalente (p. ej. "{{empresa.nombre}}" en pies de
 * página -> "Equiterm Industries" fijo).
 */

// ── Catálogo real de tokens (único soportado por el backend) ─────────
export const TOKENS = [
  ['{{contact.name}}', 'Nombre del contacto'],
  ['{{contact.email}}', 'Correo del contacto'],
  ['{{contact.company}}', 'Empresa del contacto'],
  ['{{deal.amount}}', 'Monto del negocio (si aplica)'],
  ['{{deal.name}}', 'Nombre del negocio (si aplica)'],
  ['{{unsubscribe_url}}', 'Enlace para cancelar suscripción (se agrega automáticamente al enviar)'],
];

// ── Definición de cada tipo de bloque: nombre visible, tipo de
//    renderizado (`kind`) y props por defecto al arrastrarlo/agregarlo ──
export const TIPOS = {
  titulo: { nombre: 'Título', kind: 'text', props: { texto: 'Bombas de calor con 12% de descuento', tamano: 26, color: '#141516', align: 'left', bg: '#ffffff', padding: 22, bold: true, italic: false } },
  texto: { nombre: 'Texto', kind: 'text', props: { texto: 'Hola {{contact.name}}, este trimestre tenemos precios especiales en equipo industrial para tu planta.', tamano: 14, color: '#4b5563', align: 'left', bg: '#ffffff', padding: 22, bold: false, italic: false } },
  boton: { nombre: 'Botón', kind: 'btn', props: { texto: 'Ver catálogo', url: 'https://equiterm.mx/tienda', btnBg: '#ff6213', color: '#ffffff', radio: 6, align: 'center', bg: '#ffffff', padding: 20 } },
  imagen: { nombre: 'Imagen', kind: 'img', props: { texto: 'Imagen 600 × 280', ancho: 100, align: 'center', bg: '#ffffff', padding: 20, url: '' } },
  imgtexto: { nombre: 'Imagen + texto', kind: 'imgtexto', props: { texto: 'Describe aquí el equipo, su capacidad y el beneficio principal para el cliente.', bg: '#ffffff', padding: 20 } },
  cols1: { nombre: 'Una columna', kind: 'cols', props: { n: 1, bg: '#ffffff', padding: 18 } },
  cols2: { nombre: 'Dos columnas', kind: 'cols', props: { n: 2, bg: '#ffffff', padding: 18 } },
  cols3: { nombre: 'Tres columnas', kind: 'cols', props: { n: 3, bg: '#ffffff', padding: 18 } },
  espaciador: { nombre: 'Espaciador', kind: 'space', props: { alto: 28, bg: '#ffffff' } },
  divisor: { nombre: 'Divisor', kind: 'rule', props: { color: '#e8eaed', grosor: 1, bg: '#ffffff', padding: 16 } },
  video: { nombre: 'Video', kind: 'video', props: { texto: 'Ver instalación en planta', url: '', bg: '#ffffff', padding: 20 } },
  social: { nombre: 'Redes sociales', kind: 'social', props: { align: 'center', bg: '#ffffff', padding: 18 } },
  logo: { nombre: 'Logo', kind: 'img', props: { texto: 'Logo Equiterm', ancho: 40, align: 'center', bg: '#ffffff', padding: 22, url: '' } },
  menu: { nombre: 'Menú', kind: 'text', props: { texto: 'Productos · Servicios · Contacto', tamano: 12.5, color: '#6b7280', align: 'center', bg: '#ffffff', padding: 14, bold: true, italic: false } },
  footer: { nombre: 'Pie legal', kind: 'text', props: { texto: 'Equiterm Industries · Av. Industrial 1200, Monterrey, N.L. · Cancelar suscripción', tamano: 11, color: '#9ca3af', align: 'center', bg: '#f7f8fa', padding: 22, bold: false, italic: false } },
};

// ── SVG (path data) por tipo, usado por Palette.jsx ──────────────────
export const ICONOS = {
  cols1: 'M5 4h14v16H5z', cols2: 'M4 4h7v16H4zM13 4h7v16h-7z', cols3: 'M3 4h5v16H3zM9.5 4h5v16h-5zM16 4h5v16h-5z',
  espaciador: 'M5 8h14M5 16h14M12 10.5v3', divisor: 'M4 12h16', texto: 'M4 6h16M4 11h16M4 16h10',
  titulo: 'M6 5v14M18 5v14M6 12h12', imagen: 'M4 5h16v14H4zM7 15l3.5-3.5 3 3 3-2.5 3.5 3', boton: 'M4 9h16v6H4z',
  imgtexto: 'M4 6h7v12H4zM13 8h7M13 12h7M13 16h5', video: 'M4 6h16v12H4zM10 9.5l5 2.5-5 2.5z',
  social: 'M8 13l8-4M8 15l8 3M12 6.5h.01M6 14h.01M18 18h.01', logo: 'M12 4l7 4v8l-7 4-7-4V8z',
  menu: 'M4 8h16M4 12h16M4 16h9', footer: 'M4 5h16v6H4zM4 15h16M4 19h10',
};

// ── Agrupación por categoría para la paleta izquierda ─────────────────
export const PALETA = [
  { nombre: 'Estructura', items: ['cols1', 'cols2', 'cols3', 'espaciador', 'divisor'] },
  { nombre: 'Contenido', items: ['texto', 'titulo', 'imagen', 'boton', 'imgtexto'] },
  { nombre: 'Multimedia', items: ['video', 'social'] },
  { nombre: 'Encabezado y pie', items: ['logo', 'menu', 'footer'] },
];

// ── Textos de ayuda (tooltip "?") del panel de propiedades ───────────
export const TIPS = {
  padding: 'Espacio interior del bloque. Se traduce a padding en línea, compatible con Outlook.',
  ancho: 'Porcentaje del ancho del correo (600 px). Las imágenes se exportan con width fijo.',
  url: 'Enlace al que llega el destinatario. Los clics se agrupan por esta URL en el mapa de clics.',
  texto: 'Puedes usar tokens como {{contact.name}} dentro del texto.',
};

let _bid = 0;

function B(tipo, over) {
  return {
    id: 'b' + (++_bid),
    tipo,
    props: { ...JSON.parse(JSON.stringify(TIPOS[tipo].props)), ...(over || {}) },
  };
}

/**
 * Arreglo de bloques iniciales según el tipo de plantilla, usado al
 * elegir "Lienzo vacío · armar con bloques" o al partir de un starter.
 */
export function BLOQUES_BASE(tipoPlantilla) {
  if (tipoPlantilla === 'Transaccional') {
    return [
      B('logo'),
      B('titulo', { texto: '{{contact.name}}, tu orden va en camino', tamano: 22 }),
      B('texto', { texto: 'Ya salió de nuestro almacén. Puedes seguir el envío con tu número de guía.' }),
      B('boton', { texto: 'Seguir mi envío', url: 'https://equiterm.mx/mis-ordenes' }),
      B('divisor'),
      B('footer'),
    ];
  }
  if (tipoPlantilla === 'Secuencia') {
    return [
      B('logo'),
      B('titulo', { texto: '¿Revisaste tu cotización?', tamano: 24 }),
      B('texto'),
      B('boton', { texto: 'Ver mi cotización', url: 'https://equiterm.mx/cotizaciones' }),
      B('footer'),
    ];
  }
  return [B('logo'), B('menu'), B('imagen'), B('titulo'), B('texto'), B('boton'), B('espaciador'), B('social'), B('footer')];
}

// ── 3 plantillas de arranque ("¿Con qué quieres empezar?") ────────────
// Portadas del mockup (líneas ~1070-1076) con el catálogo de tokens
// corregido: {{contacto.nombre}} -> {{contact.name}}, {{negocio.titulo}}
// -> {{deal.name}}, {{negocio.monto}} -> {{deal.amount}}, {{contacto.
// empresa}} -> {{contact.company}} (sí tiene resolutor real). El único
// token del mockup sin equivalente real es {{empresa.nombre}} (nombre de
// la propia empresa remitente) -- se reemplazó por el texto fijo
// "Equiterm Industries", que es lo que cualquier envío real mostrará ahí
// de cualquier forma.
export const STARTERS = [
  {
    tipo: 'Campaña',
    nombre: 'Promoción trimestral',
    asunto: 'Nuevas bombas de calor con 12% de descuento',
    tplTipo: 'campana',
    html:
      '<div style="font-family:Arial,sans-serif;color:#141516;padding:24px;">\n' +
      '  <h1 style="font-size:20px;margin:0 0 12px;">Hola {{contact.name}},</h1>\n' +
      '  <p style="font-size:14px;line-height:1.6;color:#4b5563;">Este trimestre tenemos 12% de descuento en nuestra línea de bombas de calor industriales.</p>\n' +
      '  <p style="margin:24px 0;">\n' +
      '    <a href="https://equiterm.mx/tienda/bombas-de-calor" style="background:#ff6213;color:#ffffff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">Ver catálogo</a>\n' +
      '  </p>\n' +
      '  <p style="font-size:12px;color:#9ca3af;">Equiterm Industries</p>\n' +
      '</div>',
  },
  {
    tipo: 'Secuencia',
    nombre: 'Seguimiento de cotización',
    asunto: '¿Revisaste la cotización que te enviamos?',
    tplTipo: 'secuencia',
    html:
      '<div style="font-family:Arial,sans-serif;color:#141516;padding:24px;">\n' +
      '  <h1 style="font-size:20px;margin:0 0 12px;">Hola {{contact.name}},</h1>\n' +
      '  <p style="font-size:14px;line-height:1.6;color:#4b5563;">\n' +
      '    Damos seguimiento a tu cotización <strong>{{deal.name}}</strong>\n' +
      '    por {{deal.amount}}. Sigue disponible para autorización.\n' +
      '  </p>\n' +
      '  <p style="margin:24px 0;">\n' +
      '    <a href="https://equiterm.mx/cotizaciones" style="background:#ff6213;color:#ffffff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">Ver mi cotización</a>\n' +
      '  </p>\n' +
      '  <p style="font-size:12px;color:#9ca3af;">Equiterm Industries · {{contact.company}}</p>\n' +
      '</div>',
  },
  {
    tipo: 'Transaccional',
    nombre: 'Orden enviada',
    asunto: 'Tu orden ya va en camino',
    tplTipo: 'transaccional',
    html:
      '<div style="font-family:Arial,sans-serif;color:#141516;padding:24px;">\n' +
      '  <h1 style="font-size:20px;margin:0 0 12px;">{{contact.name}}, tu orden va en camino</h1>\n' +
      '  <p style="font-size:14px;line-height:1.6;color:#4b5563;">Ya salió de nuestro almacén. Puedes seguir el envío con tu número de guía.</p>\n' +
      '  <p style="margin:24px 0;">\n' +
      '    <a href="https://equiterm.mx/mis-ordenes" style="background:#ff6213;color:#ffffff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:14px;">Seguir mi envío</a>\n' +
      '  </p>\n' +
      '  <p style="font-size:12px;color:#9ca3af;">Equiterm Industries</p>\n' +
      '</div>',
  },
];
