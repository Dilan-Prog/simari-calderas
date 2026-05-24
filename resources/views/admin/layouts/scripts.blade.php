<script>
    // RFC Input Validation
 const rfcInputValidate = document.querySelector('input[name="rfc"]');
    if(rfcInputValidate){
        rfcInputValidate.addEventListener('input', function() {
            this.value = this.value.setAttribute('maxlength', '13');
            this.value = this.value.setAttribute('maxlength', '12');
            this.value = this.value.setAttribute('pattern', '^[A-Z0-9]+$');
            this.value = this.value.replace(/\s+/g, '').toUpperCase();
        })
    };

    // CURP Input Validation
const curpInputValidate = document.querySelector('input[name="curp"]');
    if(curpInputValidate){
        curpInputValidate.addEventListener('input', function() {
            this.value = this.value.setAttribute('maxlength','18');
            this.value= this.value.setAttribute('pattern', '^[A-Z0-9]+$');
            this.value = this.value.replace(/\s+/g, '').toUpperCase();
        });
    };
</script>
