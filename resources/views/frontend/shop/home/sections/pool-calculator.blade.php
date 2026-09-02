@php
    // Mismo patrón de resolución de $products que product-carousel.blade.php
    // (ver comentario de referencia en ese archivo), pero simplificado: esta
    // calculadora solo admite 2 fuentes ('collection' o 'manual') porque
    // siempre recomienda de un conjunto curado corto de modelos, nunca de
    // todo el catálogo (a diferencia de product_carousel que sí soporta
    // featured/category/brand/etc.).
    $config = $section->config ?? [];
    $source = $config['source'] ?? 'collection';

    if ($source === 'collection' && !empty($config['collection_id'])) {
        $sourceCollection = \App\Models\Collection::find($config['collection_id']);
        $products = $sourceCollection ? $sourceCollection->resolveProducts() : collect();
    } else {
        $products = \App\Models\Products::whereIn('id', $config['product_ids'] ?? [])
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->get();
    }

    // Extrae el BTU de cada producto buscando en `specifications` (columna
    // JSON string SIN cast 'array' en el modelo Products — mismo parseo
    // manual que ya usa ProductController::show()) la fila cuya `key` sea
    // EXACTAMENTE "Capacidad (BTU/h)" (convención documentada del admin, no
    // un campo dedicado). Parseo defensivo: specifications null, no-JSON,
    // no-array, o sin esa key -> el producto simplemente se excluye de la
    // lista de modelos (no debe tronar la página por un producto mal
    // capturado; el admin puede seguir curando la Colección con calma).
    $modelos = $products->map(function ($p) {
        $specs = $p->specifications ? (json_decode($p->specifications, true) ?: []) : [];
        if (!is_array($specs)) {
            return null;
        }

        $btuSpec = collect($specs)->first(fn ($s) => is_array($s) && ($s['key'] ?? null) === 'Capacidad (BTU/h)');
        $btuValue = $btuSpec['value'] ?? null;
        if ($btuValue === null || !is_numeric($btuValue)) {
            return null;
        }

        return [
            'id'     => $p->id,
            'nombre' => $p->name,
            'slug'   => $p->slug,
            // Precalculado en servidor (en vez de que el JS arme la URL a
            // partir del slug) para no duplicar el prefijo de ruta
            // /producto/{slug} -- si cambia routes/web.php, este archivo no
            // se desincroniza.
            'url'    => route('product.show', $p->slug),
            'precio' => $p->price !== null ? (float) $p->price : null,
            'btu'    => (float) $btuValue,
        ];
    })->filter()->sortBy('btu')->values();

    $ciudadesDefault = [
        'CDMX' => 9, 'Toluca' => 6, 'Puebla' => 8, 'Queretaro' => 11,
        'Guadalajara' => 12, 'Leon' => 10, 'Aguascalientes' => 10,
        'Monterrey' => 13, 'Cuernavaca' => 16, 'Merida' => 21,
        'Cancun' => 22, 'Vallarta' => 21, 'Tijuana' => 11, 'Otra' => 12,
    ];

    $tarifaKwh  = (float) \App\Models\Setting::get('pool_calculator.tarifa_kwh', 5.50);
    $copNominal = (float) \App\Models\Setting::get('pool_calculator.cop_nominal', 5.5);
    $horasDia   = (float) \App\Models\Setting::get('pool_calculator.horas_operacion_dia', 10);
    $ciudades   = \App\Models\Setting::get('pool_calculator.ciudades_temp_ambiente', $ciudadesDefault);

    // Mismo número ya usado en el footer/CTAs de WhatsApp del sitio
    // (product-card, price-box, footer) -- formato sin '+' ("52...").
    $whatsappNumero = \App\Models\Setting::get('footer.phone_link', '5214494577320');

    $configParaJs = [
        'homeSectionId'  => $section->id,
        'modelos'        => $modelos->all(),
        'tarifaKwh'      => $tarifaKwh,
        'copNominal'     => $copNominal,
        'horasDia'       => $horasDia,
        'ciudades'       => $ciudades,
        'whatsappNumero' => $whatsappNumero,
    ];
@endphp

<section class="pool-calc" x-data='poolCalculator(@json($configParaJs))'>
    <div class="pool-calc__wrap">

        <div class="pool-calc__intro">
            <span class="pool-calc__eyebrow">Herramienta de ingeniería</span>
            <span class="pool-calc__badge">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Sin registro · resultado inmediato
            </span>
            <h2 class="pool-calc__title">{{ $section->resolveText($section->title, null) ?: '¿Qué bomba de calor necesita tu alberca?' }}</h2>
            <p class="pool-calc__subtitle">Ingresa las medidas y tu ciudad. Calculamos los BTU/h requeridos, el modelo de catálogo que corresponde y un rango de costo mensual de operación.</p>
        </div>

        <div class="pool-calc__card">
            <div class="pool-calc__card-head">
                <div class="pool-calc__card-head-left">
                    <span class="pool-calc__avatar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/><path d="M14 4h0M10 9h4"/></svg>
                    </span>
                    <span class="pool-calc__card-head-text">
                        <strong>Dimensionamiento térmico</strong>
                        <small>9 datos · toma menos de un minuto</small>
                    </span>
                </div>
                <span class="pool-calc__chip">Cálculo ASHRAE simplificado</span>
            </div>

            <div class="pool-calc__form">
                <div class="pool-calc__field">
                    <label for="pc-forma">Forma de la alberca</label>
                    <select id="pc-forma" x-model="forma">
                        <option value="rectangular">Rectangular</option>
                        <option value="redonda">Redonda</option>
                        <option value="irregular">Irregular</option>
                    </select>
                </div>

                <div class="pool-calc__field">
                    <label for="pc-prof">Profundidad promedio</label>
                    <div class="pool-calc__suffix-input">
                        <input id="pc-prof" type="number" min="0.8" max="3.0" step="0.1" x-model.number="prof">
                        <span>m</span>
                    </div>
                </div>

                <template x-if="forma !== 'redonda'">
                    <div class="pool-calc__field">
                        <label for="pc-largo">Largo</label>
                        <div class="pool-calc__suffix-input">
                            <input id="pc-largo" type="number" min="2" max="50" step="0.5" x-model.number="largo">
                            <span>m</span>
                        </div>
                    </div>
                </template>
                <template x-if="forma !== 'redonda'">
                    <div class="pool-calc__field">
                        <label for="pc-ancho">Ancho</label>
                        <div class="pool-calc__suffix-input">
                            <input id="pc-ancho" type="number" min="2" max="50" step="0.5" x-model.number="ancho">
                            <span>m</span>
                        </div>
                    </div>
                </template>

                <template x-if="forma === 'redonda'">
                    <div class="pool-calc__field pool-calc__field--full">
                        <label for="pc-diametro">Diámetro</label>
                        <div class="pool-calc__suffix-input">
                            <input id="pc-diametro" type="number" min="2" max="50" step="0.5" x-model.number="diametro">
                            <span>m</span>
                        </div>
                    </div>
                </template>

                <div class="pool-calc__field">
                    <label for="pc-ciudad">Ciudad</label>
                    <select id="pc-ciudad" x-model="ciudad">
                        <option value="" disabled>Selecciona tu ciudad</option>
                        <template x-for="c in Object.keys(config.ciudades)" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                    <p class="pool-calc__hint">Define clima y tarifa eléctrica de referencia.</p>
                </div>

                <div class="pool-calc__field">
                    <label for="pc-temp-actual">Temperatura actual del agua</label>
                    <div class="pool-calc__suffix-input">
                        <input id="pc-temp-actual" type="number" min="10" max="30" step="1" x-model.number="tempActual">
                        <span>°C</span>
                    </div>
                </div>

                <div class="pool-calc__field">
                    <span class="pool-calc__label">¿Tu alberca tiene cubierta térmica?</span>
                    <div class="pool-calc__pillrow">
                        {{-- cubierta se guarda como booleano real (no 'si'/'no' como
                             string) porque la fórmula de dimensionar() hace
                             `this.cubierta ? 0.55 : 1.0` -- un string 'no' sería
                             truthy en JS y rompería el factor. --}}
                        <button type="button" class="pool-calc__pill" :class="{ 'is-active': cubierta === true }" @click="cubierta = true">Sí</button>
                        <button type="button" class="pool-calc__pill" :class="{ 'is-active': cubierta === false }" @click="cubierta = false">No</button>
                    </div>
                    <p class="pool-calc__hint pool-calc__hint--callout">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 17.5v-6M12 8h.01" stroke-linecap="round"/><circle cx="12" cy="12" r="9"/></svg>
                        Con cubierta, la misma alberca pide casi la mitad de equipo. Marca la opción real: el cálculo lo refleja.
                    </p>
                </div>

                <div class="pool-calc__field">
                    <span class="pool-calc__label">Exposición al viento</span>
                    {{-- Mismo motivo que cubierta arriba: expuesta debe ser
                         booleano real para `this.expuesta ? 1.15 : 1.0`. --}}
                    <div class="pool-calc__pillrow">
                        <button type="button" class="pool-calc__pill" :class="{ 'is-active': expuesta === false }" @click="expuesta = false">Protegida</button>
                        <button type="button" class="pool-calc__pill" :class="{ 'is-active': expuesta === true }" @click="expuesta = true">Expuesta</button>
                    </div>
                    <p class="pool-calc__hint">Bardas, muros o vegetación alrededor cuentan como protección.</p>
                </div>

                <div class="pool-calc__field pool-calc__field--full">
                    <label for="pc-temp-objetivo">Temperatura objetivo: <strong x-text="tempObjetivo"></strong>°C</label>
                    <input id="pc-temp-objetivo" class="pool-calc__slider" type="range" min="26" max="32" step="1" x-model.number="tempObjetivo">
                    <div class="pool-calc__slider-ticks">
                        <span>26° templada</span>
                        <span>28° confort</span>
                        <span>32° spa</span>
                    </div>
                </div>

                <div class="pool-calc__field pool-calc__field--full">
                    <span class="pool-calc__label">Horas de calentamiento deseadas</span>
                    <div class="pool-calc__hours-grid">
                        <button type="button" class="pool-calc__hour-tile" :class="{ 'is-active': horasCalentado === 24 }" @click="horasCalentado = 24">
                            <strong>24 h</strong>
                            <span>Equipo mayor</span>
                        </button>
                        <button type="button" class="pool-calc__hour-tile" :class="{ 'is-active': horasCalentado === 48 }" @click="horasCalentado = 48">
                            <strong>48 h</strong>
                            <span>Intermedio</span>
                        </button>
                        <button type="button" class="pool-calc__hour-tile" :class="{ 'is-active': horasCalentado === 72 }" @click="horasCalentado = 72">
                            <span class="pool-calc__hour-tag">Recomendado</span>
                            <strong>72 h</strong>
                            <span>Bomba de calor</span>
                        </button>
                    </div>
                </div>

                <div class="pool-calc__field pool-calc__field--full pool-calc__cta-row">
                    <button type="button" class="pool-calc__submit" @click="dimensionar()">
                        Calcular dimensionamiento
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14M13 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <p class="pool-calc__cta-note">Sin correo, sin teléfono. El resultado aparece aquí mismo.</p>
                </div>
            </div>

            <div class="pool-calc__result" x-show="resultado" x-cloak x-transition>
                <template x-if="resultado">
                    <div>
                        <span class="pool-calc__result-eyebrow"><i></i>Tu dimensionamiento</span>

                        <div class="pool-calc__result-grid">
                            <div class="pool-calc__result-block">
                                <span class="pool-calc__result-label">Modelo recomendado</span>
                                <h3 class="pool-calc__result-model">
                                    <template x-if="resultado.modelo.url">
                                        <a :href="resultado.modelo.url" x-text="resultado.modelo.nombre" target="_blank" rel="noopener"></a>
                                    </template>
                                    <template x-if="!resultado.modelo.url">
                                        <span x-text="resultado.modelo.nombre"></span>
                                    </template>
                                </h3>
                                <div class="pool-calc__result-big">
                                    <span x-text="Math.round(resultado.btuEq).toLocaleString('es-MX')"></span>
                                    <small>BTU/h requeridos</small>
                                </div>
                                <p class="pool-calc__result-caption" x-show="resultado.modelo.url">
                                    Capacidad nominal del modelo: <span x-text="Math.round(resultado.modelo.btu).toLocaleString('es-MX')"></span> BTU/h.
                                    Cubre tu requerimiento con <span x-text="Math.max(0, Math.round((resultado.modelo.btu - resultado.btuEq) / resultado.btuEq * 100))"></span>% de holgura.
                                </p>
                                <p class="pool-calc__result-caption" x-show="!resultado.modelo.url">
                                    Ningún modelo de catálogo cubre este requerimiento — contáctanos para evaluar un proyecto a medida.
                                </p>
                            </div>

                            <div class="pool-calc__result-block">
                                <span class="pool-calc__result-label">Costo mensual estimado</span>
                                <div class="pool-calc__result-big pool-calc__result-big--cost">
                                    <span>$<span x-text="Math.round(resultado.costoMin).toLocaleString('es-MX')"></span> – $<span x-text="Math.round(resultado.costoMax).toLocaleString('es-MX')"></span></span>
                                    <small>MXN / mes</small>
                                </div>
                                <p class="pool-calc__result-caption">Rango, no cifra fija: depende de uso real, clima del mes y tarifa vigente.</p>
                            </div>
                        </div>

                        <div class="pool-calc__result-divider"></div>

                        <span class="pool-calc__assumptions-title">Supuestos usados en este cálculo</span>
                        <div class="pool-calc__assumptions-grid">
                            <div class="pool-calc__assumption">
                                <span class="pool-calc__assumption-value" x-text="Math.round(resultado.m3).toLocaleString('es-MX') + ' m³'"></span>
                                <span class="pool-calc__assumption-caption" x-text="Math.round(resultado.litros).toLocaleString('es-MX') + ' litros'"></span>
                            </div>
                            <div class="pool-calc__assumption">
                                <span class="pool-calc__assumption-value">{{ (int) $horasDia }} h / día</span>
                                <span class="pool-calc__assumption-caption">Horas de uso consideradas</span>
                            </div>
                            <div class="pool-calc__assumption">
                                <span class="pool-calc__assumption-value">${{ number_format($tarifaKwh, 2) }} / kWh</span>
                                <span class="pool-calc__assumption-caption">Tarifa configurada por el administrador</span>
                            </div>
                            <div class="pool-calc__assumption">
                                <span class="pool-calc__assumption-value" x-text="cubierta ? 'Con cubierta' : 'Sin cubierta'"></span>
                                <span class="pool-calc__assumption-caption" x-text="cubierta ? 'Retiene calor por la noche' : 'Mayor pérdida nocturna'"></span>
                            </div>
                        </div>

                        <div class="pool-calc__cta-final">
                            <button type="button" class="pool-calc__whatsapp" @click="enviarWhatsApp()">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.1-1.3A10 10 0 1 0 12 2zm0 2a8 8 0 1 1-4.1 14.8l-.4-.2-2.8.7.7-2.7-.2-.4A8 8 0 0 1 12 4zm-3 4.3c-.3 0-.7.1-1 .5-.4.4-.9 1-.9 2s.7 2 1 2.4c.4.5 1.9 2.9 4.6 4 2.2.9 2.7.7 3.2.7.6-.1 1.8-.7 2-1.5.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4l-1.7-.8c-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1-.8-.3-1.5-.8-2.1-1.4-.5-.5-1-1.2-1.4-1.9-.1-.2 0-.4.1-.5l.4-.5c.1-.2.2-.3.2-.5.1-.2 0-.4 0-.5C10 12 9.4 10.5 9.2 10c-.2-.5-.4-.5-.6-.5H8z"/></svg>
                                Enviar mi cálculo por WhatsApp
                            </button>
                            <p class="pool-calc__whatsapp-note">Llega con el resumen ya redactado: medidas, BTU/h, modelo y ciudad. Un ingeniero lo revisa y confirma.</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <p class="pool-calc__disclaimer" x-show="resultado" x-cloak>Cálculo orientativo, no cotización final.</p>
    </div>
</section>
