@php
    $config = $section->config ?? [];
    $reviews = $servicePage->visibleReviews()->get();

    $hasStats = !is_null($servicePage->rating_average_displayed);
    $hasReviews = $reviews->isNotEmpty();
@endphp

@if ($hasStats || $hasReviews)
<section class="svc-rating" @if($previewMode) data-section-id="{{ $section->id }}" @endif>

    @if ($hasStats)
        @php
            $avg = (float) $servicePage->rating_average_displayed;
            $roundedAvg = (int) round($avg);
            $distribution = $servicePage->rating_distribution ?? [];
            $distTotal = collect($distribution)->sum();
        @endphp
        <div class="svc-rating__panel">
            <div>
                <div class="svc-rating__eyebrow">Opiniones verificadas</div>
                <div class="svc-rating__score">{{ number_format($avg, 1) }}</div>
                <div class="svc-rating__stars">
                    @for ($i = 1; $i <= 5; $i++)
                        {{ $i <= $roundedAvg ? '★' : '☆' }}
                    @endfor
                </div>
                @if (!is_null($servicePage->rating_total_rated))
                    <div class="svc-rating__count">{{ $servicePage->rating_total_rated }} servicios calificados</div>
                @endif
                <div class="svc-rating__verified-badge">✓ Solo clientes con orden de servicio cerrada</div>
            </div>
            <div>
                @for ($star = 5; $star >= 1; $star--)
                    @php
                        $starCount = (int) ($distribution[(string) $star] ?? $distribution[$star] ?? 0);
                        $pct = $distTotal > 0 ? round(($starCount / $distTotal) * 100) : 0;
                    @endphp
                    <div class="svc-rating__dist-row">
                        <span>{{ $star }} ★</span>
                        <span class="svc-rating__dist-bar"><span class="svc-rating__dist-bar-fill" style="width: {{ $pct }}%"></span></span>
                        <span>{{ $starCount }}</span>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    @php
        $kpis = [];
        if (!is_null($servicePage->rating_recommend_percent)) {
            $kpis[] = ['value' => number_format($servicePage->rating_recommend_percent, 0) . '%', 'label' => 'recomendaría el servicio'];
        }
        if (!is_null($servicePage->rating_punctuality_average)) {
            $kpis[] = ['value' => number_format($servicePage->rating_punctuality_average, 1), 'label' => 'en puntualidad de cuadrilla'];
        }
        if (!is_null($servicePage->rating_total_rated)) {
            $kpis[] = [
                'value' => $servicePage->rating_total_rated,
                'label' => $servicePage->rating_since_year ? 'servicios calificados desde ' . $servicePage->rating_since_year : 'servicios calificados',
            ];
        }
        if (!is_null($servicePage->rating_recurring_clients)) {
            $kpis[] = ['value' => $servicePage->rating_recurring_clients, 'label' => 'clientes recurrentes'];
        }
    @endphp
    @if (!empty($kpis))
        <div class="svc-rating__kpis">
            @foreach ($kpis as $kpi)
                <div class="svc-rating__kpi">
                    <div class="svc-rating__kpi-value">{{ $kpi['value'] }}</div>
                    <div class="svc-rating__kpi-label">{{ $kpi['label'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($hasReviews)
        @php
            $availableCategories = collect(\App\Models\ServicePageReview::CATEGORIES)
                ->filter(fn ($label, $slug) => $reviews->contains(fn ($r) => in_array($slug, $r->categories ?? [], true)));
            $reviewsPerPage = (int) ($config['reviews_per_page'] ?? 3);
            if ($reviewsPerPage < 1) {
                $reviewsPerPage = 3;
            }
        @endphp
        <div x-data="{ filter: 'todas', showAll: false }">
            <h2 class="svc-rating__list-title">{{ $section->title ?: 'Lo que dicen nuestros clientes' }}</h2>

            @if ($availableCategories->isNotEmpty())
                <div class="svc-rating__filters">
                    <button type="button" class="svc-rating__filter" :class="{ 'is-active': filter === 'todas' }" @click="filter = 'todas'">Todas</button>
                    @foreach ($availableCategories as $slug => $label)
                        <button type="button" class="svc-rating__filter" :class="{ 'is-active': filter === '{{ $slug }}' }" @click="filter = '{{ $slug }}'">{{ $label }}</button>
                    @endforeach
                </div>
            @endif

            @foreach ($reviews as $index => $review)
                @php
                    $metaParts = array_filter([
                        $review->customer_role,
                        $review->customer_company,
                        trim(collect([$review->customer_city, $review->customer_state])->filter()->implode(', ')),
                    ]);
                    $photoCount = count($review->photo_urls ?? []);
                    $reviewCategories = $review->categories ?? [];
                @endphp
                <div class="svc-review"
                    data-categories='@json($reviewCategories)'
                    x-show="(filter === 'todas' || JSON.parse($el.dataset.categories || '[]').includes(filter)) && (showAll || {{ $index }} < {{ $reviewsPerPage }})"
                >
                    <div class="svc-review__head">
                        <div class="svc-review__who">
                            <div class="svc-review__avatar">{{ $review->customer_initials }}</div>
                            <div>
                                <div class="svc-review__name-row">
                                    <span class="svc-review__name">{{ $review->customer_name }}</span>
                                    @if ($review->is_verified)
                                        <span class="svc-review__verified">✓ Cliente verificado</span>
                                    @endif
                                </div>
                                @if (!empty($metaParts))
                                    <div class="svc-review__meta">{{ implode(' · ', $metaParts) }}</div>
                                @endif
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="svc-review__stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    {{ $i <= $review->rating ? '★' : '☆' }}
                                @endfor
                            </div>
                            @if ($review->review_date)
                                <div class="svc-review__date">{{ $review->review_date->translatedFormat('F Y') }}</div>
                            @endif
                        </div>
                    </div>

                    <p class="svc-review__comment">&ldquo;{{ $review->comment }}&rdquo;</p>

                    @if (!empty($reviewCategories) || $photoCount > 0)
                        <div class="svc-review__tags">
                            @foreach ($reviewCategories as $slug)
                                @if (isset(\App\Models\ServicePageReview::CATEGORIES[$slug]))
                                    <span class="svc-review__tag">{{ \App\Models\ServicePageReview::CATEGORIES[$slug] }}</span>
                                @endif
                            @endforeach
                            @if ($photoCount > 0)
                                <span class="svc-review__photos">🖼 {{ $photoCount }} fotos</span>
                            @endif
                        </div>
                    @endif

                    @if (!empty($review->business_response))
                        <div class="svc-review__response">
                            <div class="svc-review__response-label">Respuesta de Equiterm</div>
                            <p class="svc-review__response-text">{{ $review->business_response }}</p>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($reviews->count() > $reviewsPerPage)
                <button type="button" class="svc-rating__more" x-show="!showAll" @click="showAll = true">Ver las {{ $reviews->count() }} opiniones</button>
            @endif
        </div>
    @endif
</section>
@endif
