@push('scripts')
<script>
(function () {
    const rows = document.getElementById('spFaqRows');

    function reindex() {
        Array.from(rows.querySelectorAll('.hs-faq-row')).forEach((row, i) => {
            row.querySelector('.hs-faq-question').name = `faq_items[${i}][question]`;
            row.querySelector('.hs-faq-answer').name = `faq_items[${i}][answer]`;
            row.querySelector('.hs-faq-row-num').textContent = i + 1;
        });
    }

    function addRow(question = '', answer = '') {
        const row = document.createElement('div');
        row.className = 'hs-faq-row';
        row.innerHTML = `
            <div class="hs-faq-row-head">
                <span class="hs-faq-row-num"></span>
                <div class="hs-faq-row-actions">
                    <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                </div>
            </div>
            <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta">
            <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta"></textarea>`;
        row.querySelector('.hs-faq-question').value = question;
        row.querySelector('.hs-faq-answer').value = answer;
        row.querySelector('.hs-faq-remove').addEventListener('click', () => {
            row.remove();
            reindex();
        });
        rows.appendChild(row);
        reindex();
    }

    document.getElementById('btnAddSpFaq').addEventListener('click', () => addRow());

    (window.SP_EXISTING_FAQS ?? []).forEach(f => addRow(f.question ?? '', f.answer ?? ''));
})();
</script>
@endpush
