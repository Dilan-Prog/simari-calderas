{{-- Import supplier-products modal. Cuando se incluye con $scopedSupplier
     (desde la pestaña Productos de un proveedor), la importación queda
     bloqueada a ese proveedor — la columna "Proveedor" del archivo se vuelve
     opcional y, si se llena, debe coincidir con él. --}}
<div id="importSupplierProductsModal" class="del-confirm-overlay">
    <div class="del-confirm-box prod-import-box">
        <h2 class="del-confirm-title">
            @if (isset($scopedSupplier))
                Importar Productos de {{ $scopedSupplier->company_name }}
            @else
                Importar Proveedores Productos
            @endif
        </h2>
        <p class="del-confirm-desc">
            @if (isset($scopedSupplier))
                Sube un archivo Excel (.xlsx, .xls) o CSV para crear o actualizar los productos de este proveedor.
                La columna "Proveedor" es opcional aquí — se asume {{ $scopedSupplier->company_name }}.
            @else
                Sube un archivo Excel (.xlsx, .xls) o CSV para crear o actualizar vínculos proveedor-producto.
            @endif
        </p>

        <div class="prod-import-templates">
            <button type="button" class="prod-import-template-link"
                data-download-url="{{ route('admin.suppliers.products.import.template', isset($scopedSupplier) ? ['supplier_id' => $scopedSupplier->id] : []) }}">
                Descargar plantilla con ejemplos
            </button>
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
