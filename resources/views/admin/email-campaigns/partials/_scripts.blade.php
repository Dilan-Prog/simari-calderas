<script>
(function () {
    var btn = document.getElementById('ecs-send-now-btn');
    if (!btn) {
        return;
    }

    var errorBox = document.getElementById('ecs-send-error');

    btn.addEventListener('click', function () {
        var confirmed = confirm('¿Enviar esta campaña ahora a todos los suscriptores de la lista?');
        if (!confirmed) {
            return;
        }

        var sendUrl = btn.getAttribute('data-send-url');
        var redirectUrl = btn.getAttribute('data-redirect-url');
        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        btn.disabled = true;
        btn.textContent = 'Enviando...';
        if (errorBox) {
            errorBox.style.display = 'none';
            errorBox.textContent = '';
        }

        fetch(sendUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('No se pudo enviar la campaña.');
                }
                window.location.href = redirectUrl;
            })
            .catch(function (error) {
                btn.disabled = false;
                btn.textContent = 'Enviar ahora';
                if (errorBox) {
                    errorBox.textContent = error.message || 'Ocurrió un error al enviar la campaña.';
                    errorBox.style.display = 'block';
                } else {
                    alert(error.message || 'Ocurrió un error al enviar la campaña.');
                }
            });
    });
})();
</script>
