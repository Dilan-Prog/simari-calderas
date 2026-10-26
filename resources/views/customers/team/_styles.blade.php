<style>
    .tm-tabs { display: flex; gap: .25rem; border-bottom: 1px solid var(--ts-border, #E5E7EB); margin: 0 0 1rem; }
    .tm-tab { border: 0; background: transparent; cursor: pointer; padding: .625rem 1rem; font-size: .875rem; font-weight: 600; color: var(--ts-text-muted, #6B7280); border-bottom: 2px solid transparent; margin-bottom: -1px; display: inline-flex; align-items: center; gap: .5rem; }
    .tm-tab:hover { color: var(--ts-text-title, #111827); }
    .tm-tab.is-active { color: var(--ts-primary, #ff6213); border-bottom-color: var(--ts-primary, #ff6213); }
    .tm-tab__count { font-size: .6875rem; font-weight: 700; background: #F3F4F6; color: #4B5563; border-radius: 9999px; padding: .1rem .5rem; }
    .tm-panel[hidden] { display: none; }

    .tm-person { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .tm-avatar { flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; background: #E5E7EB; color: #374151; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .tm-avatar--owner { background: var(--ts-primary, #ff6213); color: #fff; }
    .tm-person__name { font-weight: 600; color: var(--ts-text-title, #111827); overflow-wrap: anywhere; }
    .tm-person__mail { font-size: .75rem; color: var(--ts-text-muted, #6B7280); overflow-wrap: anywhere; }
    .tm-desc { max-width: 360px; color: var(--ts-text-muted, #6B7280); }
    .tm-muted { color: var(--ts-text-light, #9CA3AF); font-size: .75rem; }
    .tm-hint { margin: .75rem 0 0; font-size: .8125rem; color: var(--ts-text-muted, #6B7280); }
    .tm-hint-sm { font-size: .75rem; color: var(--ts-text-muted, #6B7280); }
    .tm-error { font-size: .75rem; color: #DC2626; }

    .tm-badge { display: inline-flex; align-items: center; padding: .15rem .6rem; border-radius: 9999px; font-size: .6875rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; margin-left: .25rem; }
    .tm-badge--owner { background: #FFF4EC; color: #C2410C; border-color: #FED7AA; }
    .tm-badge--sys { background: #EFF6FF; color: #2563EB; border-color: #BFDBFE; }
    .tm-badge--ok { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; margin-left: 0; }
    .tm-badge--off { background: #F3F4F6; color: #6B7280; border-color: #D1D5DB; margin-left: 0; }

    .tm-actions { display: flex; flex-wrap: wrap; gap: .375rem; justify-content: flex-end; }
    .tm-actions form { margin: 0; }
    .tm-btn-sm { height: 32px; padding: 0 .65rem; font-size: .75rem; }
    .tm-btn-sm[disabled] { opacity: .5; cursor: not-allowed; }

    .tm-modal { position: fixed; inset: 0; z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(17, 24, 39, .55); }
    .tm-modal.is-open { display: flex; }
    .tm-modal__card { background: #fff; border-radius: 12px; width: 100%; max-width: 560px; max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
    .tm-modal__card--sm { max-width: 420px; }
    .tm-modal__card--lg { max-width: 760px; }
    .tm-modal form { display: flex; flex-direction: column; min-height: 0; margin: 0; }
    .tm-modal__head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--ts-border-light, #F3F4F6); }
    .tm-modal__title { margin: 0; font-size: 1.0625rem; font-weight: 700; color: var(--ts-text-title, #111827); }
    .tm-modal__close { border: 0; background: transparent; cursor: pointer; width: 36px; height: 36px; border-radius: 8px; color: var(--ts-text-muted, #6B7280); font-size: 1.125rem; line-height: 1; }
    .tm-modal__close:hover { background: #F3F4F6; }
    .tm-modal__body { padding: 1.25rem; overflow-y: auto; display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
    .tm-modal__body--one { grid-template-columns: 1fr; }
    .tm-modal__body .tm-full { grid-column: 1 / -1; }
    .tm-modal__foot { display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 1.25rem; border-top: 1px solid var(--ts-border-light, #F3F4F6); }
    .tm-confirm { padding: 1.5rem 1.25rem .5rem; text-align: center; font-size: .875rem; color: var(--ts-text-body, #374151); line-height: 1.5; }
    .tm-confirm strong { color: var(--ts-text-title, #111827); overflow-wrap: anywhere; }
    body.tm-modal-open { overflow: hidden; }

    .tm-matrix-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .5rem; }
    .tm-link { border: 0; background: transparent; color: var(--ts-primary, #ff6213); font-size: .75rem; font-weight: 600; cursor: pointer; padding: .25rem .5rem; }
    .tm-link:hover { text-decoration: underline; }
    .tm-matrix-wrap { border: 1px solid var(--ts-border, #E5E7EB); border-radius: 8px; overflow: auto; max-height: 46vh; }
    .tm-matrix { width: 100%; border-collapse: collapse; font-size: .8125rem; }
    .tm-matrix thead th { position: sticky; top: 0; background: #F9FAFB; text-align: center; padding: .5rem; font-size: .6875rem; text-transform: uppercase; letter-spacing: .06em; color: #6B7280; z-index: 1; }
    .tm-matrix thead th:first-child, .tm-matrix td:first-child { text-align: left; padding-left: .75rem; }
    .tm-matrix td { padding: .45rem .5rem; text-align: center; border-top: 1px solid var(--ts-border-light, #F3F4F6); }
    .tm-matrix__group th { background: #F3F4F6; text-align: left; padding: .4rem .75rem; font-size: .6875rem; text-transform: uppercase; letter-spacing: .06em; color: #4B5563; }
    .tm-matrix input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--ts-primary, #ff6213); cursor: pointer; }

    @media (max-width: 640px) {
        .tm-modal { align-items: flex-end; padding: 0; }
        .tm-modal__card { max-width: none; border-radius: 14px 14px 0 0; max-height: 94dvh; }
        .tm-modal__body { grid-template-columns: 1fr; }
        .tm-modal__foot { flex-direction: column-reverse; }
        .tm-modal__foot .ts-btn { width: 100%; justify-content: center; height: 46px; }
        .tm-actions { justify-content: flex-start; }
    }
</style>
