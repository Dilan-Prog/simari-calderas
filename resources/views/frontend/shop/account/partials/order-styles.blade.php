{{-- Estilos autocontenidos (prefijo eqo-) de "Mis pedidos". Se incluyen una vez
     en la tienda (/cuenta) y en el portal (/customer/pedidos) para que ambas
     vistas compartan el mismo markup sin depender del CSS de cada layout. --}}
<style>
    .eqo { --eqo-accent: var(--secondary-color, var(--ts-primary, #ff6213)); color: #141516; font-size: 14px; }
    .eqo *, .eqo *::before, .eqo *::after { box-sizing: border-box; }
    .eqo a { color: inherit; }

    .eqo-card { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 10px; padding: 22px; margin-bottom: 18px; }
    .eqo-card--flush { padding: 0; overflow: hidden; }
    .eqo-card__title { font-size: 14.5px; font-weight: 700; margin: 0 0 14px; }
    .eqo-card--flush > .eqo-card__title { padding: 16px 20px; margin: 0; border-bottom: 1px solid rgba(0,0,0,.06); }

    .eqo-pill { display: inline-block; font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 999px; white-space: nowrap; }

    /* Listado */
    .eqo-list { padding: 0; overflow: hidden; }
    .eqo-row { display: flex; align-items: center; gap: 18px; padding: 18px 20px; border-bottom: 1px solid rgba(0,0,0,.06);
        flex-wrap: wrap; text-decoration: none; color: inherit; cursor: pointer; transition: background .15s; }
    .eqo-row:last-child { border-bottom: none; }
    .eqo-row:hover { background: #fafafa; }
    .eqo-row__id { flex: 1 1 150px; min-width: 0; }
    .eqo-row__num { font-size: 13.5px; font-weight: 800; }
    .eqo-row__date { font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .eqo-row__items { flex: 0 1 110px; font-size: 13px; color: #4b5563; }
    .eqo-row__total { font-size: 15px; font-weight: 800; min-width: 110px; text-align: right; }
    .eqo-row__chev { color: #9ca3af; flex: 0 0 auto; }
    .eqo-empty { text-align: center; color: #9ca3af; padding: 44px 16px; border: 1px dashed rgba(0,0,0,.12); border-radius: 10px; }
    .eqo-empty p { margin: 0 0 6px; }
    .eqo-note { font-size: 12px; color: #9ca3af; margin: 4px 0 0; text-align: center; }

    /* Cabecera de detalle */
    .eqo-head { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
    .eqo-head__title { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
    .eqo-head__date { font-size: 13px; color: #9ca3af; }
    .eqo-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #4b5563; text-decoration: none; margin-bottom: 16px; }
    .eqo-back:hover { color: #141516; }

    /* Línea de tiempo */
    .eqo-timeline { display: flex; align-items: flex-start; }
    .eqo-step { flex: 1; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; min-width: 0; padding: 0 4px; }
    .eqo-step__line { position: absolute; top: 15px; right: 50%; width: 100%; height: 2px; background: #e5e5e5; }
    .eqo-step__line.is-done { background: var(--eqo-accent); }
    .eqo-step__line.is-terminal { background: #c81e1e; }
    .eqo-step__dot { width: 32px; height: 32px; border-radius: 50%; background: #f0f0f0; color: #9ca3af; display: flex; align-items: center;
        justify-content: center; font-size: 14px; font-weight: 800; position: relative; z-index: 1; }
    .eqo-step__dot.is-done { background: var(--eqo-accent); color: #fff; }
    .eqo-step__dot.is-terminal { background: #c81e1e; color: #fff; }
    .eqo-step__label { font-size: 12.5px; font-weight: 700; color: #9ca3af; margin-top: 10px; }
    .eqo-step__label.is-done { color: #141516; }
    .eqo-step__date { font-size: 11.5px; color: #9ca3af; margin-top: 2px; }
    .eqo-timeline-hint { font-size: 12.5px; color: #b45309; margin: 14px 0 0; text-align: center; }

    /* Rejilla principal */
    .eqo-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 18px; align-items: start; }
    .eqo-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .eqo-info-grid .eqo-card { margin-bottom: 0; }
    .eqo-stack > .eqo-card:last-child { margin-bottom: 0; }

    .eqo-item { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,.06); }
    .eqo-item:last-child { border-bottom: none; }
    .eqo-item__img { width: 60px; height: 60px; flex: 0 0 60px; border-radius: 6px; background: #f5f6f7; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .eqo-item__img img { width: 100%; height: 100%; object-fit: contain; }
    .eqo-item__info { flex: 1; min-width: 0; }
    .eqo-item__name { font-size: 13.5px; font-weight: 700; line-height: 1.35; overflow-wrap: anywhere; }
    .eqo-item__meta { font-size: 12px; color: #9ca3af; margin-top: 4px; }
    .eqo-item__price { font-size: 14px; font-weight: 800; white-space: nowrap; text-align: right; }
    .eqo-item__price small { display: block; font-size: 11.5px; font-weight: 500; color: #9ca3af; }

    .eqo-text { font-size: 13px; color: #4b5563; line-height: 1.7; overflow-wrap: anywhere; }
    .eqo-text strong { color: #141516; }
    .eqo-kv { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 5px 0; color: #4b5563; }
    .eqo-kv span:last-child { text-align: right; color: #141516; overflow-wrap: anywhere; }
    .eqo-kv__sub { font-size: 12px; color: #9ca3af; }
    .eqo-sum { display: flex; justify-content: space-between; font-size: 13px; color: #4b5563; padding: 6px 0; }
    .eqo-sum--total { font-size: 16px; font-weight: 800; color: #141516; padding-top: 14px; margin-top: 8px; border-top: 1px solid rgba(0,0,0,.08); }
    .eqo-sum--discount span:last-child { color: #178a3c; }
    .eqo-tag { display: inline-block; font-size: 11.5px; font-weight: 700; padding: 3px 9px; border-radius: 999px; }
    .eqo-tag--ok { background: #e6f6ee; color: #0f7a4f; }
    .eqo-tag--warn { background: #fef3c7; color: #b45309; }
    .eqo-tag--bad { background: #fdecec; color: #c81e1e; }

    .eqo-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; margin-top: 12px; padding: 9px 16px; border-radius: 8px;
        background: var(--eqo-accent); color: #fff !important; font-size: 13px; font-weight: 700; text-decoration: none; border: 0; }
    .eqo-btn:hover { filter: brightness(.93); }

    @media (max-width: 900px) {
        .eqo-grid { grid-template-columns: 1fr; }
        .eqo-info-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .eqo-card { padding: 16px; }
        .eqo-row { gap: 10px; padding: 14px 16px; }
        .eqo-row__items { flex: 1 1 auto; }
        .eqo-row__total { min-width: 0; text-align: left; flex: 1 1 auto; }
        .eqo-head__title { font-size: 19px; }
        /* Línea de tiempo en vertical */
        .eqo-timeline { flex-direction: column; gap: 0; padding-left: 6px; }
        .eqo-step { flex-direction: row; align-items: center; text-align: left; gap: 12px; padding: 0 0 18px; width: 100%; flex: none; flex-wrap: wrap; }
        .eqo-step:last-child { padding-bottom: 0; }
        .eqo-step__line { top: auto; bottom: 100%; right: auto; left: 15px; width: 2px; height: 18px; }
        .eqo-step__label { margin-top: 0; }
        .eqo-step__date { margin: 0 0 0 auto; }
        .eqo-item { padding: 14px 16px; gap: 12px; }
        .eqo-item__img { width: 52px; height: 52px; flex-basis: 52px; }
    }
</style>
