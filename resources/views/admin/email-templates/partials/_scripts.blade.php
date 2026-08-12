{{--
    JS del token-picker + preview para create/edit de plantillas de correo.
    La lógica vive en resources/js/admin/email-template-editor.js (bundleada
    vía Vite), que lee su configuración de los data-attributes puestos en
    #emailTemplateForm (ver _form.blade.php: data-is-edit, data-preview-url).
--}}
@push('scripts')
    @vite(['resources/js/admin/email-template-editor.js'])
@endpush
