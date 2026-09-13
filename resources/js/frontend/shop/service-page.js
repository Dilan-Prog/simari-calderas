import Alpine from './alpine-init.js';

// La página de Servicio no tenía JS propio (bug preexistente: nunca cargaba
// Alpine, aunque ya incluía frontend.shop.home.sections.faq, que depende de
// x-data). Mismo patrón que collection.js: arranca Alpine para header,
// buscador, y los nuevos bloques de Servicios (tabs, galería, rating).
Alpine.start();
