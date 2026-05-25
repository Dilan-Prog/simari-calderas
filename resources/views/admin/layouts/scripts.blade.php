<script>
const rfcInputValidate = document.querySelector('input[name="rfc"]');
    if (rfcInputValidate) {
        rfcInputValidate.setAttribute('maxlength', '13');
        rfcInputValidate.setAttribute('pattern', '^[A-Z0-9]+$');
        rfcInputValidate.addEventListener('input', function () {
            this.value = this.value.replace(/\s+/g, '').toUpperCase();
        });
    }

const curpInputValidate = document.querySelector('input[name="curp"]');
    if(curpInputValidate){
        curpInputValidate.setAttribute('maxlength', '18');
        curpInputValidate.setAttribute('pattern', '^[A-Z0-9]+$');
        curpInputValidate.addEventListener('input', function() {
            this.value = this.value.replace(/\s+/g, '').toUpperCase();
        });
    };

</script>
