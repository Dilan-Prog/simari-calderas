import Sortable from 'sortablejs';

(function () {
    'use strict';

    var board = document.getElementById('pipelineBoard');
    if (!board) return;

    var csrfMeta   = document.querySelector('meta[name="csrf-token"]');
    var csrfToken  = csrfMeta ? csrfMeta.getAttribute('content') : '';
    // La vista renderiza la ruta real (admin.deals.move-stage) con un
    // placeholder __DEAL_ID__ que sustituimos aquí; evita hardcodear el
    // prefijo de la ruta en el JS.
    var urlTemplate = board.dataset.moveStageUrlTemplate || '';

    function buildMoveStageUrl(dealId) {
        if (urlTemplate) {
            return urlTemplate.replace('__DEAL_ID__', dealId);
        }
        return '/admin/negocios/' + dealId + '/mover-etapa';
    }

    function recalcColumn(listEl) {
        var column = listEl.closest('.pipeline-column');
        if (!column) return;

        var cards = listEl.querySelectorAll('.pipeline-card[data-amount]');
        // Si las tarjetas no traen data-amount (no se agregó en la vista),
        // no se puede recalcular el total sin otra petición; se omite.
        if (cards.length === 0 && listEl.querySelectorAll('.pipeline-card').length > 0) {
            return;
        }

        var total = 0;
        cards.forEach(function (card) {
            total += parseFloat(card.getAttribute('data-amount')) || 0;
        });

        var countEl = column.querySelector('[data-column-count]');
        var totalEl = column.querySelector('[data-column-total]');
        var cardCount = listEl.querySelectorAll('.pipeline-card').length;

        if (countEl) {
            countEl.textContent = cardCount + ' negocio(s)';
        }
        if (totalEl) {
            totalEl.textContent = '$' + total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }
    }

    function showValidationError(errors) {
        var message = 'No se pudo mover el negocio.';
        if (errors && typeof errors === 'object') {
            var messages = [];
            Object.keys(errors).forEach(function (key) {
                var val = errors[key];
                if (Array.isArray(val)) {
                    messages = messages.concat(val);
                } else {
                    messages.push(val);
                }
            });
            if (messages.length > 0) {
                message = messages.join('\n');
            }
        }
        alert(message);
    }

    var lists = board.querySelectorAll('[data-stage-id]');

    lists.forEach(function (listEl) {
        // Solo inicializar Sortable sobre el contenedor de tarjetas
        // (.pipeline-column-list), no sobre la columna completa.
        if (!listEl.classList.contains('pipeline-column-list')) return;

        new Sortable(listEl, {
            group: 'deals-kanban',
            animation: 150,
            draggable: '.pipeline-card',
            ghostClass: 'sortable-ghost',
            dragClass: 'dragging',
            onEnd: function (evt) {
                var card = evt.item;
                var dealId    = card.getAttribute('data-deal-id');
                var toStageId = evt.to.getAttribute('data-stage-id');

                if (!dealId || !toStageId) return;

                // Nada cambió (misma columna, misma posición)
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) {
                    return;
                }

                fetch(buildMoveStageUrl(dealId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ to_stage_id: toStageId }),
                })
                .then(function (response) {
                    if (response.status === 422) {
                        return response.json().then(function (data) {
                            // Revertir visualmente: mover la tarjeta de vuelta
                            // a su columna/posición original.
                            var fromList = evt.from;
                            var referenceNode = fromList.children[evt.oldIndex] || null;
                            if (referenceNode) {
                                fromList.insertBefore(card, referenceNode);
                            } else {
                                fromList.appendChild(card);
                            }

                            recalcColumn(evt.from);
                            recalcColumn(evt.to);

                            showValidationError(data && data.errors);
                        });
                    }

                    if (!response.ok) {
                        throw new Error('Error inesperado al mover el negocio (status ' + response.status + ').');
                    }

                    // Éxito: recalcular contadores/sumas de columna origen y destino.
                    recalcColumn(evt.from);
                    recalcColumn(evt.to);
                })
                .catch(function (err) {
                    console.error(err);
                    alert('Ocurrió un error de red al mover el negocio.');
                });
            },
        });
    });
})();
