/* JS mínimo para las páginas de la tienda que no tienen script propio (Nosotros, avisos legales, confirmación de
   checkout). Arranca Alpine con los componentes compartidos (shared.js: megaMenu, buscador, carrito...), sin esto
   el menú de Categorías/Servicios del encabezado no responde. */
import Alpine from './alpine-init.js';

Alpine.start();
