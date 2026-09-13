{{-- Estadísticas de marketing editables a mano — se guardan junto con el
     resto del formulario (botón "Guardar Cambios"). Deliberadamente
     separadas de las reseñas reales (_reviews.blade.php): estos campos
     NUNCA alimentan el JSON-LD AggregateRating, solo son copy visual. Ver
     comentario en la migración add_rating_stats_to_service_pages_table. --}}
@php $sp = $servicePage; $dist = $sp->rating_distribution ?? []; @endphp
<h3 style="margin-top:20px;margin-bottom:4px;">Rating y reseñas — Promedio mostrado</h3>
<div class="show-user-divider"></div>
<p class="hs-config-note">
    Contenido curado por el equipo, igual que la FAQ. El sitio público no recibe reseñas de clientes directamente:
    el staff captura las reseñas reales más abajo. Estas cifras son de marketing (no tienen por qué coincidir
    con el número de reseñas capturadas) y nunca se usan en el marcado SEO — el marcado usa siempre el conteo real.
</p>

<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">Promedio mostrado (0–5)</label>
        <input type="number" step="0.1" min="0" max="5" class="users-manager-input" name="rating_average_displayed" value="{{ old('rating_average_displayed', $sp->rating_average_displayed) }}">
    </div>
    <div>
        <label class="supliers-manager-slider-label">Total de servicios calificados</label>
        <input type="number" min="0" class="users-manager-input" name="rating_total_rated" value="{{ old('rating_total_rated', $sp->rating_total_rated) }}">
    </div>
</div>

<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">% que recomendaría el servicio</label>
        <input type="number" step="0.1" min="0" max="100" class="users-manager-input" name="rating_recommend_percent" value="{{ old('rating_recommend_percent', $sp->rating_recommend_percent) }}">
    </div>
    <div>
        <label class="supliers-manager-slider-label">Puntualidad de cuadrilla (0–5)</label>
        <input type="number" step="0.1" min="0" max="5" class="users-manager-input" name="rating_punctuality_average" value="{{ old('rating_punctuality_average', $sp->rating_punctuality_average) }}">
    </div>
</div>

<div class="user-manager-form">
    <div>
        <label class="supliers-manager-slider-label">Clientes recurrentes</label>
        <input type="number" min="0" class="users-manager-input" name="rating_recurring_clients" value="{{ old('rating_recurring_clients', $sp->rating_recurring_clients) }}">
    </div>
    <div>
        <label class="supliers-manager-slider-label">Calificando desde (año, opcional)</label>
        <input type="number" min="2000" max="2100" class="users-manager-input" name="rating_since_year" value="{{ old('rating_since_year', $sp->rating_since_year) }}">
    </div>
</div>

<div class="users-manager-email-camp">
    <label class="supliers-manager-slider-label">Distribución por estrella</label>
    <div class="user-manager-form" style="grid-template-columns:repeat(5,1fr);">
        @for ($star = 5; $star >= 1; $star--)
            <div>
                <label class="supliers-manager-slider-label">{{ $star }} ★</label>
                <input type="number" min="0" class="users-manager-input" name="rating_distribution[{{ $star }}]" value="{{ old('rating_distribution.' . $star, $dist[$star] ?? $dist[(string) $star] ?? '') }}">
            </div>
        @endfor
    </div>
</div>
