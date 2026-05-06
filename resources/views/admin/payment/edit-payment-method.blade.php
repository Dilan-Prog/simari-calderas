<!--
    This file is a template for the payment method editing modal.
    It is dynamically injected into the main modal when "Edit" is clicked.
    It contains a form with fields to edit the name, logo, description, instructions, commission, order, and status of the payment method.
-->

<div class="payment-method-modal-drawer">

    <!-- HEADER -->
    <div class="payment-method-modal-drawer-header">
        <h2>Editar Método de Pago</h2>
        <button type="button" id="closeModal">&times;</button>
    </div>

    <!-- FORM -->
    <form id="paymentMethodForm">

        <div class="form-group">
            <label>Nombre *</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>URL del Logo</label>
            <input type="text" name="logo">
        </div>

        <div class="form-group">
            <label>Descripción *</label>
            <textarea name="description" required></textarea>
        </div>

        <div class="form-group">
            <label>Instrucciones</label>
            <textarea name="instructions"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Comisión (%) *</label>
                <input type="number" name="commission" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label>Orden *</label>
                <input type="number" name="order" min="1" required>
            </div>
        </div>

        <div class="form-group">
            <label>Estado *</label>
            <select name="status">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>

        <!-- FOOTER -->
        <div class="payment-method-modal-footer">
            <button type="button" class="btn-cancel" id="cancelModal">Cancelar</button>
            <button type="submit" class="btn-create">Guardar</button>
        </div>

    </form>
</div>