{{--
    Toast centrado en pantalla (no es un despliegue lateral). Uso:
    showCenterToast('Mensaje', 'success'|'error');

    Autocontenido: incluye su contenedor y la función JS. Inclúyelo una vez
    por página con @include('admin.components.center-toast').
--}}
<div id="centerToastRoot" class="center-toast-root"></div>
<script>
    function showCenterToast(message, type = 'success', title = null) {
        const root = document.getElementById('centerToastRoot');
        if (!root) return;

        const existing = root.querySelector('.center-toast');
        if (existing) existing.remove();

        const isError = type === 'error';
        const toast = document.createElement('div');
        toast.className = 'center-toast ' + (isError ? 'center-toast--error' : 'center-toast--success');
        toast.innerHTML = `
            <div class="center-toast__icon">
                ${isError
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg>'
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>'}
            </div>
            <div class="center-toast__body">
                <p class="center-toast__title">${title ?? (isError ? 'Error' : 'Listo')}</p>
                <p class="center-toast__message"></p>
            </div>
        `;
        toast.querySelector('.center-toast__message').textContent = message;
        root.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('is-leaving');
            setTimeout(() => toast.remove(), 200);
        }, 2500);
    }

    // Permite mostrar un toast justo después de un location.reload() (la
    // acción guarda el mensaje aquí antes de recargar; al cargar la página
    // se muestra una sola vez y se borra).
    function queueCenterToast(message, type = 'success', title = null) {
        sessionStorage.setItem('pendingCenterToast', JSON.stringify({ message, type, title }));
    }

    document.addEventListener('DOMContentLoaded', () => {
        const raw = sessionStorage.getItem('pendingCenterToast');
        if (!raw) return;
        sessionStorage.removeItem('pendingCenterToast');
        try {
            const { message, type, title } = JSON.parse(raw);
            showCenterToast(message, type, title);
        } catch (e) {}
    });
</script>
