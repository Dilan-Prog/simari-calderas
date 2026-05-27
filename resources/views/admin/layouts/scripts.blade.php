<script>
function applyUpperAlphanumericValidation(input, max) {
    if (!input) return;
    input.maxLength = max;
    input.pattern   = '^[A-Z0-9]+$';
    input.addEventListener('input', () => {

        input.value = input.value
            .replace(/[^A-Z0-9]/gi, '')
            .toUpperCase();
    });
}

document.querySelectorAll('#create_rfc, #edit_rfc')
    .forEach(input => applyUpperAlphanumericValidation(input, 13));

const curpInput = document.querySelector('[name="curp"], #edit_curp');
applyUpperAlphanumericValidation(curpInput, 18);
</script>
