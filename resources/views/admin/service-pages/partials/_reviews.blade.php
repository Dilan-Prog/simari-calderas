@php
    $visibleCount = $servicePage->reviews->where('is_visible', true)->count();
    $totalCount = $servicePage->reviews->count();
@endphp
<div style="display:flex;justify-content:space-between;align-items:center;margin:36px 0 16px;">
    <div>
        <h2 style="margin:0;">Reseñas capturadas</h2>
        <p class="hs-config-note" style="margin-top:2px;">{{ $visibleCount }} visibles de {{ $totalCount }}</p>
    </div>
    <button type="button" class="button-primary size-adjustment" id="btnNewServiceReview" style="background:#ff6213;border-color:#ff6213;">
        + Agregar reseña
    </button>
</div>

<div id="serviceReviewsList" class="hs-repeat-rows" data-base-url="{{ url('/admin/servicios-web/' . $servicePage->id . '/resenas') }}">
    @forelse ($servicePage->reviews as $review)
        <div class="service-review-row" data-id="{{ $review->id }}" draggable="true">
            <div class="service-review-row__drag">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>
            <div class="service-review-row__body">
                <div class="service-review-row__head">
                    <strong>{{ $review->customer_name }}</strong>
                    <span class="service-review-row__stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                    @if ($review->is_verified)
                        <span class="users-manager-badge status" style="font-size:10px;">Verificado</span>
                    @endif
                    <span class="users-manager-badge {{ $review->is_visible ? 'status' : 'status-inactive' }}" style="font-size:10px;">
                        {{ $review->is_visible ? 'Visible' : 'Oculta' }}
                    </span>
                </div>
                <p class="service-review-row__comment">{{ \Illuminate\Support\Str::limit($review->comment, 140) }}</p>
            </div>
            <div class="header-right-user-manager">
                <button type="button" class="table-users-manager-action-btn edit btn-edit-service-review" data-id="{{ $review->id }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                </button>
                <button type="button" class="table-users-manager-action-btn delete btn-delete-service-review" data-id="{{ $review->id }}" data-name="{{ e($review->customer_name) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                </button>
            </div>
        </div>
    @empty
        <p class="hs-config-note">Todavía no hay reseñas capturadas para este servicio.</p>
    @endforelse
</div>
