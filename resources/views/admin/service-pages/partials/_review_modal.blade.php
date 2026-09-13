<div id="serviceReviewModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="serviceReviewModalTitle">Nueva Reseña</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeServiceReviewModal">✕</button>
        </div>

        <div id="service-review-modal-errors" class="user-manager-errors" style="display:none;"></div>

        <form class="user-manager-modal-body" id="serviceReviewForm">
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Cliente <span style="color:red">*</span></label>
                    <input type="text" class="users-manager-input" name="customer_name" id="srCustomerName" required>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Puesto (opcional)</label>
                    <input type="text" class="users-manager-input" name="customer_role" id="srCustomerRole" placeholder="Jefe de mantenimiento">
                </div>
            </div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Empresa (opcional)</label>
                    <input type="text" class="users-manager-input" name="customer_company" id="srCustomerCompany">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Fecha</label>
                    <input type="date" class="users-manager-input" name="review_date" id="srReviewDate">
                </div>
            </div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Ciudad (opcional)</label>
                    <input type="text" class="users-manager-input" name="customer_city" id="srCustomerCity">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado (opcional)</label>
                    <input type="text" class="users-manager-input" name="customer_state" id="srCustomerState" placeholder="JAL">
                </div>
            </div>

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Calificación <span style="color:red">*</span></label>
                <div class="service-review-stars-input" id="srRatingStars">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" class="service-review-star" data-value="{{ $i }}">★</button>
                    @endfor
                </div>
                <input type="hidden" name="rating" id="srRating" value="5">
            </div>

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Comentario <span style="color:red">*</span> · <span id="srCommentCount">0</span>/240</label>
                <textarea class="users-manager-input client-modal-textarea" name="comment" id="srComment" rows="3" maxlength="240" required></textarea>
            </div>

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Categorías</label>
                <div class="hs-product-chips" style="flex-wrap:wrap;">
                    @foreach (\App\Models\ServicePageReview::CATEGORIES as $key => $label)
                        <label style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:999px;padding:4px 10px;font-size:12.5px;cursor:pointer;">
                            <input type="checkbox" class="sr-category-checkbox" value="{{ $key }}"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">
                        <input type="checkbox" name="is_verified" id="srIsVerified" value="1"> Cliente verificado
                    </label>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">
                        <input type="checkbox" name="is_visible" id="srIsVisible" value="1" checked> Visible en público
                    </label>
                </div>
            </div>

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Respuesta de Equiterm (opcional)</label>
                <textarea class="users-manager-input client-modal-textarea" name="business_response" id="srBusinessResponse" rows="2"></textarea>
            </div>
            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">Fecha de respuesta (opcional)</label>
                <input type="date" class="users-manager-input" name="business_response_date" id="srBusinessResponseDate">
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelServiceReviewModal" class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="serviceReviewSubmitBtn">Crear Reseña</button>
            </div>
        </form>
    </div>
</div>
