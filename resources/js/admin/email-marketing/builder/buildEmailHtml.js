import { TIPOS } from './blockTypes.js';

/**
 * buildEmailHtml.js
 *
 * Genera el `html_body` final a partir del arreglo de bloques del
 * armador visual: tablas anidadas + estilos en línea, compatible con
 * Outlook/Gmail/Apple Mail. Portado literal desde el mockup
 * `Email Marketing.dc.html` (método `buildEmailHtml()`, líneas
 * ~1008-1024) como función pura -- no depende de estado de React.
 *
 * No requiere corrección de tokens: no genera ningún token, solo
 * intercala el texto que el usuario haya escrito en cada bloque (que sí
 * puede incluir tokens, ya validados contra el catálogo real en
 * blockTypes.js/TOKENS).
 */
export function buildEmailHtml(blocks) {
  const row = (inner, p) =>
    '  <tr><td align="left" bgcolor="' + (p.bg || '#ffffff') + '" style="padding:' + (p.padding || 0) + 'px;">\n' + inner + '\n  </td></tr>';

  const body = (blocks || [])
    .map((b) => {
      const p = b.props;
      const k = TIPOS[b.tipo].kind;

      if (k === 'text') {
        return row(
          '    <div style="font-family:Arial,sans-serif;font-size:' + p.tamano + 'px;line-height:1.6;color:' + p.color + ';text-align:' + p.align + ';font-weight:' + (p.bold ? 'bold' : 'normal') + ';">' + p.texto + '</div>',
          p
        );
      }
      if (k === 'btn') {
        return row(
          '    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="' + p.align + '"><tr><td bgcolor="' + p.btnBg + '" style="border-radius:' + p.radio + 'px;"><a href="' + p.url + '" style="display:inline-block;padding:12px 22px;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:' + p.color + ';text-decoration:none;">' + p.texto + '</a></td></tr></table>',
          p
        );
      }
      if (k === 'img') {
        return row(
          '    <img src="' + (p.url || 'https://equiterm.mx/img/placeholder.png') + '" width="' + Math.round((600 * p.ancho) / 100) + '" alt="' + p.texto + '" style="display:block;border:0;max-width:100%;height:auto;margin:0 auto;"/>',
          p
        );
      }
      if (k === 'rule') {
        return row(
          '    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td height="' + p.grosor + '" bgcolor="' + p.color + '" style="line-height:0;font-size:0;">&nbsp;</td></tr></table>',
          p
        );
      }
      if (k === 'space') {
        return '  <tr><td height="' + p.alto + '" bgcolor="' + p.bg + '" style="line-height:0;font-size:0;">&nbsp;</td></tr>';
      }
      if (k === 'cols') {
        const w = Math.floor(100 / p.n);
        let tds = '';
        for (let i = 0; i < p.n; i++) {
          tds += '<td width="' + w + '%" valign="top" style="font-family:Arial,sans-serif;font-size:14px;color:#4b5563;">Contenido ' + (i + 1) + '</td>';
        }
        return row('    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' + tds + '</tr></table>', p);
      }
      if (k === 'imgtexto') {
        return row(
          '    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="38%" valign="top"><img src="https://equiterm.mx/img/placeholder.png" width="220" alt="" style="display:block;border:0;max-width:100%;"/></td><td width="62%" valign="top" style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#4b5563;padding-left:14px;">' + p.texto + '</td></tr></table>',
          p
        );
      }
      if (k === 'video') {
        return row(
          '    <a href="' + (p.url || '#') + '"><img src="https://equiterm.mx/img/video-thumb.png" width="600" alt="' + p.texto + '" style="display:block;border:0;max-width:100%;"/></a>',
          p
        );
      }
      if (k === 'social') {
        return row(
          '    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="' + p.align + '"><tr><td style="padding:0 5px;"><a href="#"><img src="https://equiterm.mx/img/fb.png" width="30" height="30" alt="Facebook" style="border:0;"/></a></td><td style="padding:0 5px;"><a href="#"><img src="https://equiterm.mx/img/li.png" width="30" height="30" alt="LinkedIn" style="border:0;"/></a></td><td style="padding:0 5px;"><a href="#"><img src="https://equiterm.mx/img/ig.png" width="30" height="30" alt="Instagram" style="border:0;"/></a></td></tr></table>',
          p
        );
      }
      return '';
    })
    .join('\n');

  return (
    '<!-- Generado por el armador de bloques · tablas anidadas + estilos en línea -->\n' +
    '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f3f5">\n' +
    ' <tr><td align="center" style="padding:24px 12px;">\n' +
    '  <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:600px;max-width:600px;">\n' +
    body +
    '\n  </table>\n' +
    ' </td></tr>\n' +
    '</table>'
  );
}

export default buildEmailHtml;
