{{-- Apertura / cierre genérico de modales .mn-modal (portal del cliente, Monitoreo). --}}
<script>
(function () {
    var lastTrigger = null;

    window.mnModal = {
        open: function (modal, focusSel) {
            if (typeof modal === 'string') modal = document.getElementById(modal);
            if (!modal) return;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('mn-modal-open');
            var el = focusSel ? modal.querySelector(focusSel) : modal.querySelector('input:not([type=hidden]), select, textarea, button');
            if (el) setTimeout(function () { el.focus(); }, 30);
        },
        close: function (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.mn-modal.is-open')) document.body.classList.remove('mn-modal-open');
            if (lastTrigger) { try { lastTrigger.focus(); } catch (e) {} lastTrigger = null; }
        },
        setTrigger: function (el) { lastTrigger = el; }
    };

    document.addEventListener('click', function (ev) {
        var opener = ev.target.closest('[data-mn-open]');
        if (opener && !opener.hasAttribute('data-mn-custom')) {
            lastTrigger = opener;
            window.mnModal.open(opener.getAttribute('data-mn-open'));
            return;
        }
        if (ev.target.closest('[data-mn-close]')) {
            window.mnModal.close(ev.target.closest('.mn-modal'));
            return;
        }
        if (ev.target.classList && ev.target.classList.contains('mn-modal')) window.mnModal.close(ev.target);
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') document.querySelectorAll('.mn-modal.is-open').forEach(window.mnModal.close);
    });
})();
</script>
