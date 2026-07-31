{{-- Import supplier-products modal --}}
<div id="importSupplierProductsModal" class="del-confirm-overlay">
    <div class="del-confirm-box prod-import-box">
        <h2 class="del-confirm-title">Importar Proveedores Productos</h2>
        <p class="del-confirm-desc">
            Sube un archivo Excel (.xlsx, .xls) o CSV para crear o actualizar vínculos proveedor-producto.
        </p>

        <div class="prod-import-templates">
            <a href="{{ route('admin.suppliers.products.import.template') }}" class="prod-import-template-link">
                Descargar plantilla con ejemplos
            </a>
        </div>

        <div class="prod-import-dropzone" id="spImportDropzone" role="button" tabindex="0">
            <input type="file" id="spImportFileInput" accept=".xlsx,.xls,.csv" style="display:none">
            <svg class="prod-import-dropzone-icon" xmlns="http://www.w3.org/2000/svg" width="30" height="30"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 3v12" />
                <path d="m7 8 5-5 5 5" />
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            </svg>
            <p class="prod-import-dropzone-text" id="spImportDropzoneText">
                <strong>Haz clic para seleccionar un archivo</strong>
            </p>
            <p class="prod-import-dropzone-hint" id="spImportDropzoneHint">.xlsx, .xls o .csv</p>
        </div>

        <div id="spImportStatus" class="prod-import-status" style="display:none"></div>
        <div id="spImportResultDetail" class="prod-import-result" style="display:none"></div>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="spImportModalCancel">Cerrar</button>
            <button type="button" class="button-primary size-adjustment" id="btnDoSupplierProductImport" disabled>
                Subir e Importar
            </button>
        </div>
    </div>
</div>
