{{-- Duplicados: banner de último escaneo + grupos detectados --}}
<div class="dup-scan-banner" id="dupScanBanner">
    @if ($lastScan)
        @if ($lastScan->status === 'failed')
            <span class="dup-scan-banner__text dup-scan-banner__text--error">
                El último escaneo falló: {{ $lastScan->error_message }}
            </span>
        @else
            <span class="dup-scan-banner__text">
                Último escaneo: {{ optional($lastScan->finished_at)->diffForHumans() ?? 'en curso' }}
                · {{ $lastScan->images_scanned }} imágenes revisadas
                · <span id="dupPendingCount">{{ $duplicateGroups->count() }}</span> grupo(s) pendiente(s)
            </span>
        @endif
    @else
        <span class="dup-scan-banner__text">Todavía no se ha escaneado el catálogo en busca de duplicados.</span>
    @endif
</div>

{{-- FIX: ambos bloques (vacío y con grupos) se dejan siempre en el DOM,
     alternando su visibilidad por JS — así "Aplicar"/"Descartar" pueden
     quitar solo la tarjeta del grupo resuelto (ver _duplicates_scripts) en
     vez de depender de una recarga completa que vuelva a traer TODO el
     estado del servidor para mostrar un solo grupo menos. --}}
<div class="gal-empty" id="dupEmptyState" style="{{ $duplicateGroups->isEmpty() ? '' : 'display:none' }}">
    <p>
        @if ($lastScan)
            No se encontraron imágenes duplicadas pendientes de revisar.
        @else
            Presiona <strong>Escanear duplicados</strong> para analizar todo el catálogo (productos, marcas,
            categorías, colecciones y banners).
        @endif
    </p>
</div>

<div class="dup-groups" id="dupGroupsList" style="{{ $duplicateGroups->isEmpty() ? 'display:none' : '' }}">
    @foreach ($duplicateGroups as $group)
            <div class="dup-group" data-group-id="{{ $group['id'] }}" data-images="{{ json_encode($group['images']) }}">
                <div class="dup-group__header">
                    <span>Grupo #{{ $group['id'] }} · {{ count($group['images']) }} imágenes</span>
                    <div class="dup-group__actions">
                        <button type="button" class="button-secondary size-adjustment dup-btn-dismiss"
                            data-group-id="{{ $group['id'] }}">Descartar</button>
                        <button type="button" class="button-primary size-adjustment dup-btn-replace"
                            style="background:#ff6213;border-color:#ff6213;" data-group-id="{{ $group['id'] }}" disabled>
                            Reemplazar con…
                        </button>
                    </div>
                </div>
                <div class="gal-grid dup-group__grid">
                    @foreach ($group['images'] as $img)
                        <label class="gal-card dup-card">
                            <input type="checkbox" class="dup-checkbox" data-group-id="{{ $group['id'] }}"
                                data-image-url="{{ $img['image_url'] }}">
                            <div class="gal-card__img">
                                <img src="{{ $img['url'] }}" alt="{{ $img['label'] }}" loading="lazy">
                                <div class="dup-card__check">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                                </div>
                                @if ($img['used_in'] > 1)
                                    <span class="dup-card__badge">usada en {{ $img['used_in'] }} lugares</span>
                                @endif
                            </div>
                            <div class="gal-card__meta">
                                <div class="gal-card__label" title="{{ $img['label'] }}">{{ $img['label'] }}</div>
                                <div class="gal-card__sublabel">{{ $img['sublabel'] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

