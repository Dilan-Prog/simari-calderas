@php
    $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $dateText = $report->service_date ? $report->service_date->format('j') . ' ' . $months[$report->service_date->month - 1] . ' ' . $report->service_date->format('Y') : null;
    $rows = array_filter([
        'Reporte'   => $report->report_number,
        'Fecha'     => $dateText,
        'Ubicación' => trim((string) $report->location),
    ], fn ($v) => $v !== null && $v !== '');
    $phoneLine = collect([$tech['name'], $dateText, $report->report_number])->filter()->implode(' · ');
    $stars = (int) $rating->rating;
    $selectedCard = (int) old('card_id', $prefill['card_id'] ?? 0);
    $selectedChips = collect(old('chips', $prefill['chips'] ?? []))->map(fn ($v) => (int) $v)->all();
    $commentValue = old('comment', $prefill['comment'] ?? '');
    $formAction = $action . ($editing ? '?editar=1' : '');
@endphp
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $texts['survey.title'] }} - {{ $report->report_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;background:#f4f5f6;font-family:'Inter',system-ui,sans-serif;color:#141516;-webkit-font-smoothing:antialiased}
        *{box-sizing:border-box}
        a{color:#ff6213;text-decoration:none}
        a:hover{color:#de4a00}
        input,textarea,button{font-family:inherit}
        textarea::placeholder{color:#9ca3af}
        @media (max-width:599px){[data-desk]{display:none!important}}
        @media (min-width:600px){[data-phone]{display:none!important}}
        .sv-page{min-height:100vh;display:flex;flex-direction:column}
        .sv-header{background:#141516}
        .sv-header-in{width:95%;max-width:1160px;margin:0 auto;padding:14px 0;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .sv-header img{height:clamp(28px,6vw,38px);width:auto;display:block}
        .sv-main{flex:1;width:95%;max-width:1160px;margin:0 auto;padding:clamp(20px,4vw,48px) 0 56px}
        .sv-layout{display:flex;flex-wrap:wrap;gap:clamp(16px,2.5vw,28px);align-items:flex-start}
        .sv-aside{flex:1 1 300px;max-width:100%;background:#141516;color:#fff;border-radius:10px;padding:clamp(18px,3vw,28px);display:flex;flex-direction:column;gap:20px}
        .sv-eyebrow{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#ff6213}
        .sv-svc-title{font-size:clamp(18px,2.4vw,21px);font-weight:800;line-height:1.25;text-wrap:balance}
        .sv-tech{display:flex;align-items:center;gap:12px;padding:14px;border-radius:8px;background:rgba(255,255,255,.06)}
        .sv-avatar{width:44px;height:44px;border-radius:50%;background:#ff6213;color:#141516;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;flex-shrink:0}
        .sv-facts{display:grid;grid-template-columns:auto minmax(0,1fr);gap:10px 16px;font-size:14px}
        .sv-facts .k{color:#9ca3af}
        .sv-facts .v{font-weight:600;text-align:right}
        .sv-section{flex:2 1 460px;max-width:100%;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:10px}
        .sv-body{padding:clamp(22px,3.5vw,36px);display:flex;flex-direction:column;gap:28px}
        .sv-h1{margin:0;font-size:clamp(24px,3.6vw,32px);font-weight:800;letter-spacing:-.02em;line-height:1.15}
        .sv-sub{margin:0;font-size:15px;color:#4b5563;text-wrap:pretty}
        .sv-err{background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;font-size:14px}
        .sv-levels{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:clamp(6px,1.2vw,12px)}
        .sv-level{min-height:clamp(76px,12vw,96px);padding:10px 4px;border-radius:8px;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(0,0,0,.12);background:#fff;transition:all .15s ease}
        .sv-level.on{border:2px solid #ff6213;background:#fff4ed}
        .sv-level .st{font-size:clamp(26px,5vw,34px);line-height:1;color:#d1d5db;transition:color .15s}
        .sv-level .st.lit{color:#ff6213}
        .sv-level .tx{font-size:clamp(11px,1.8vw,13.5px);font-weight:700;color:#4b5563;line-height:1.15;text-align:center}
        .sv-level.on .tx{color:#141516}
        .sv-level:focus-visible,.sv-pstar:focus-visible,.sv-chip:focus-within{outline:3px solid rgba(255,98,19,.4);outline-offset:2px}
        .sv-phone-rate{display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px 0 4px}
        .sv-pstars{display:flex;justify-content:space-between;width:100%;max-width:320px}
        .sv-pstar{width:56px;height:56px;padding:0;border:none;background:transparent;cursor:pointer;font-size:48px;line-height:1;color:#d1d5db;transition:transform .15s,color .15s}
        .sv-pstar.lit{color:#ff6213}
        .sv-pstar.on{transform:scale(1.12)}
        .sv-plabel{font-size:18px;font-weight:800;color:#9ca3af;min-height:24px}
        .sv-plabel.set{color:#141516}
        .sv-more{display:flex;flex-direction:column;gap:24px}
        .sv-more[hidden]{display:none}
        .sv-q-title{font-size:15px;font-weight:700}
        .sv-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
        .sv-chip{position:relative;white-space:nowrap;flex:0 0 auto;min-height:42px;padding:8px 16px;border-radius:999px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;border:1px solid rgba(0,0,0,.15);background:#fff;color:#141516;transition:all .15s}
        .sv-chip input{position:absolute;opacity:0;inset:0;width:100%;height:100%;margin:0;cursor:pointer}
        .sv-chip.on{background:#141516;border-color:#141516;color:#fff}
        .sv-chip[hidden]{display:none}
        .sv-group[hidden]{display:none}
        .sv-field{display:flex;flex-direction:column;gap:10px}
        .sv-field .lb{font-size:15px;font-weight:700}
        .sv-field .lb small{font-weight:400;color:#6b7280;font-size:inherit}
        .sv-field textarea{width:100%;resize:vertical;background:#fff;border:1px solid rgba(0,0,0,.15);border-radius:6px;padding:12px 14px;font-size:15px;color:#141516;outline:none;line-height:1.5}
        .sv-field textarea:focus{border-color:#ff6213;box-shadow:0 0 0 3px rgba(255,98,19,.15)}
        .sv-count{font-size:12.5px;color:#6b7280;align-self:flex-end;font-variant-numeric:tabular-nums}
        .sv-lock{display:flex;align-items:center;gap:8px;color:#6b7280;font-size:13px}
        .sv-lock svg{flex-shrink:0}
        .sv-lock input{flex:1;min-width:0;border:0;background:transparent;padding:0;font:inherit;font-weight:600;color:#4b5563;-webkit-text-fill-color:#4b5563;opacity:1;cursor:not-allowed}
        .sv-foot-desk{padding:16px clamp(22px,3.5vw,36px);background:#f9fafb;border-top:1px solid rgba(0,0,0,.08);border-radius:0 0 10px 10px;display:flex;flex-wrap:wrap-reverse;gap:10px;align-items:center;justify-content:flex-end}
        .sv-btn-skip{flex:1 1 140px;max-width:220px;min-height:48px;border-radius:6px;background:#fff;border:1px solid rgba(0,0,0,.15);color:#141516;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none}
        .sv-btn-skip:hover{border-color:#141516;color:#141516}
        .sv-btn-send{flex:2 1 220px;max-width:100%;min-height:48px;border-radius:6px;border:none;font-size:15px;font-weight:700;cursor:pointer;background:#ff6213;color:#fff;transition:background .15s}
        .sv-btn-send:hover:not(:disabled){background:#de4a00}
        .sv-btn-send:disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed}
        .sv-foot-phone{position:sticky;bottom:0;padding:14px 18px 18px;background:#fff;border-top:1px solid rgba(0,0,0,.08);border-radius:0 0 10px 10px;display:flex;flex-direction:column;gap:6px}
        .sv-foot-phone .sv-btn-send{width:100%;max-width:none;min-height:52px;font-size:16px;border-radius:8px;flex:none}
        .sv-foot-phone .sv-text-skip{width:100%;min-height:44px;border:none;background:transparent;color:#4b5563;font-size:15px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none}
        .sv-sent{max-width:560px;margin:0 auto;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:10px;padding:clamp(32px,5vw,48px) clamp(20px,4vw,40px);display:flex;flex-direction:column;align-items:center;gap:14px;text-align:center}
        .sv-check{width:64px;height:64px;border-radius:50%;background:#ff6213;color:#fff;display:flex;align-items:center;justify-content:center}
        .sv-sent h2{margin:0;font-size:clamp(22px,3vw,26px);font-weight:800;letter-spacing:-.01em}
        .sv-sent-stars{display:flex;gap:4px;font-size:24px;color:#ff6213;letter-spacing:2px}
        .sv-sent p{margin:0;font-size:15px;line-height:1.55;color:#4b5563;max-width:400px;text-wrap:pretty}
        .sv-sent-chips{display:flex;flex-wrap:wrap;gap:6px;justify-content:center}
        .sv-sent-chip{font-size:12.5px;font-weight:600;padding:5px 12px;border-radius:999px;background:#f3f4f6;color:#374151}
        .sv-sent-comment{font-size:14px;color:#4b5563;background:#f9fafb;border-radius:8px;padding:12px 14px;white-space:pre-line;max-width:420px;width:100%;text-align:left}
        .sv-sent-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:10px;width:100%}
        .sv-sent-actions a,.sv-sent-actions .sv-edit{flex:1 1 160px;max-width:220px;min-height:46px;border-radius:6px;font-weight:700;font-size:15px;display:flex;align-items:center;justify-content:center;text-decoration:none}
        .sv-sent-actions a.dark{background:#141516;color:#fff}
        .sv-sent-actions .sv-edit{background:#fff;border:1px solid rgba(0,0,0,.15);color:#141516}
        .sv-sent-actions .sv-edit:hover{border-color:#141516}
        .sv-note{font-size:12.5px;color:#6b7280}
        @media (max-width:599px){
            .sv-foot-phone{padding:14px 16px calc(16px + env(safe-area-inset-bottom,0px));gap:10px}
            .sv-foot-phone .sv-btn-send{min-height:60px;font-size:17px;border-radius:10px}
            .sv-foot-phone .sv-text-skip{min-height:54px;font-size:16px;border:1px solid rgba(0,0,0,.15);border-radius:10px;background:#fff;color:#141516;font-weight:700}
            .sv-level,.sv-chip{min-height:52px}
            .sv-chip{font-size:15px;padding:10px 18px}
            .sv-sent-actions{flex-direction:column}
            .sv-sent-actions a,.sv-sent-actions .sv-edit{flex:none;width:100%;max-width:none;min-height:58px;font-size:17px;border-radius:10px}
            .sv-sent-actions .sv-edit{border:1px solid rgba(0,0,0,.2)}
        }
        @media (prefers-reduced-motion:reduce){*{transition:none!important}}
    </style>
</head>
<body>
<div class="sv-page">
    <header class="sv-header">
        <div class="sv-header-in">
            <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="Equiterm Industries">
        </div>
    </header>

    <main class="sv-main">
    @if ($done)
        <div class="sv-sent" role="status">
            <div class="sv-check" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </div>
            <h2>{{ $justSubmitted ? '¡Gracias por tu calificación!' : 'Tu calificación' }}</h2>
            <div class="sv-sent-stars" role="img" aria-label="{{ $stars }} de 5 estrellas - {{ $rating->card_label }}" title="{{ $rating->card_label }}">{{ str_repeat('★', $stars) }}{{ str_repeat('☆', max(0, 5 - $stars)) }}</div>
            <p>{{ $thanksMessage }}</p>

            @if ($rating->answers->isNotEmpty())
                <div class="sv-sent-chips">
                    @foreach ($rating->answers as $a)<span class="sv-sent-chip">{{ $a->question_label }}</span>@endforeach
                </div>
            @endif
            @if ($rating->comment)<div class="sv-sent-comment">{{ $rating->comment }}</div>@endif
            <span class="sv-note">{{ $texts['survey.name_label'] }}: {{ $rating->customer_name }}</span>

            <div class="sv-sent-actions">
                <a class="dark" href="{{ $reportUrl }}">Ver reporte</a>
                @if ($canEdit)
                    <a class="sv-edit" href="{{ $editUrl }}">Editar calificación</a>
                @endif
            </div>
            @unless ($canEdit)<span class="sv-note">Esta calificación ya no se puede editar.</span>@endunless
        </div>
    @else
        <form method="POST" action="{{ $formAction }}" id="svForm" novalidate style="display:contents">
            @csrf
            <input type="hidden" name="card_id" id="svCardId" value="{{ $selectedCard ?: '' }}">
            <div class="sv-layout">

                <aside class="sv-aside">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <span class="sv-eyebrow">Servicio concluido</span>
                        <span class="sv-svc-title">{{ $serviceTitle }}</span>
                    </div>
                    <div data-phone style="display:contents;"><span style="font-size:14px;color:#9ca3af;margin-top:-12px;">{{ $phoneLine }}</span></div>
                    <div data-desk style="display:contents;">
                        <div class="sv-tech">
                            <div class="sv-avatar">{{ $tech['initials'] }}</div>
                            <div style="display:flex;flex-direction:column;gap:2px;min-width:0;">
                                <span style="font-weight:700;font-size:15px;">{{ $tech['name'] }}</span>
                                <span style="font-size:13px;color:#9ca3af;">Técnico de servicio</span>
                            </div>
                        </div>
                        @if ($rows)
                            <div class="sv-facts">
                                @foreach ($rows as $k => $v)
                                    <span class="k">{{ $k }}</span><span class="v">{{ $v }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </aside>

                <section class="sv-section">
                    <div class="sv-body">
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <h1 class="sv-h1">{{ $texts['survey.title'] }}</h1>
                            <p class="sv-sub">{{ $texts['survey.subtitle'] }}</p>
                        </div>

                        @if ($errors->any())
                            <div class="sv-err" role="alert">{{ $errors->first() }}</div>
                        @endif

                        {{-- Teléfono: fila de estrellas --}}
                        <div data-phone style="display:contents;">
                            <div class="sv-phone-rate">
                                <div class="sv-pstars" role="radiogroup" aria-label="{{ $texts['survey.title'] }}">
                                    @foreach ($cards as $card)
                                        <button type="button" class="sv-pstar" data-card="{{ $card->id }}" data-stars="{{ $card->stars }}" data-label="{{ $card->label }}" role="radio" aria-checked="false" aria-label="{{ $card->label }}">★</button>
                                    @endforeach
                                </div>
                                <span class="sv-plabel" id="svPhoneLabel">Toca una estrella</span>
                            </div>
                        </div>

                        {{-- Escritorio: tarjetas de nivel --}}
                        <div data-desk style="display:contents;">
                            <div class="sv-levels" id="svLevels" role="radiogroup" aria-label="{{ $texts['survey.title'] }}">
                                @foreach ($cards as $card)
                                    <button type="button" class="sv-level" data-card="{{ $card->id }}" data-stars="{{ $card->stars }}" role="radio" aria-checked="false">
                                        <span class="st">★</span>
                                        <span class="tx">{{ $card->label }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="sv-more" id="svMore" hidden>
                            @foreach (['positive' => $texts['survey.good_title'], 'negative' => $texts['survey.bad_title']] as $kind => $title)
                                @php $group = collect($chips)->where('kind', $kind); @endphp
                                @if ($group->isNotEmpty())
                                    <div class="sv-group" data-kind="{{ $kind }}" hidden>
                                        <span class="sv-q-title">{{ $title }}</span>
                                        <div class="sv-chips">
                                            @foreach ($group as $c)
                                                <label class="sv-chip" data-chip>
                                                    <input type="checkbox" name="chips[]" value="{{ $c['id'] }}" @checked(in_array($c['id'], $selectedChips, true)) disabled>
                                                    <span data-mark>+</span><span>{{ $c['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <div class="sv-field">
                                <label class="lb" for="svComment">{{ $texts['survey.comment_label'] }}@if (!str_contains(mb_strtolower($texts['survey.comment_label']), 'opcional')) <small>(opcional)</small>@endif</label>
                                <textarea id="svComment" name="comment" rows="4" maxlength="{{ $commentMax }}" placeholder="Cuéntanos más sobre tu experiencia con el servicio…">{{ $commentValue }}</textarea>
                                <span class="sv-count"><span id="svCount">0</span>/{{ $commentMax }}</span>
                            </div>

                            <div class="sv-lock">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <span>{{ $texts['survey.name_label'] }}:</span>
                                <input type="text" value="{{ $rating->customer_name }}" readonly disabled aria-label="{{ $texts['survey.name_label'] }} (no editable)">
                            </div>
                        </div>
                    </div>

                    <div data-phone style="display:contents;">
                        <div class="sv-foot-phone">
                            <button type="submit" class="sv-btn-send" disabled>{{ $texts['survey.submit_label'] }}</button>
                            @if ($editing)
                                <a class="sv-text-skip" href="{{ $action }}">Cancelar</a>
                            @else
                                <button type="submit" class="sv-text-skip" form="svSkipForm">Ahora no</button>
                            @endif
                        </div>
                    </div>
                    <div data-desk style="display:contents;">
                        <div class="sv-foot-desk">
                            @if ($editing)
                                <a class="sv-btn-skip" href="{{ $action }}">Cancelar</a>
                            @else
                                <button type="submit" class="sv-btn-skip" form="svSkipForm">Ahora no</button>
                            @endif
                            <button type="submit" class="sv-btn-send" disabled>{{ $texts['survey.submit_label'] }}</button>
                        </div>
                    </div>
                </section>
            </div>
        </form>
        <form method="POST" action="{{ $skipAction }}" id="svSkipForm">@csrf</form>
    @endif
    </main>
</div>

@unless ($done)
<script>
(function () {
    var form = document.getElementById('svForm');
    var cardInput = document.getElementById('svCardId');
    var more = document.getElementById('svMore');
    var comment = document.getElementById('svComment');
    var count = document.getElementById('svCount');
    var phoneLabel = document.getElementById('svPhoneLabel');
    var sendBtns = form.querySelectorAll('.sv-btn-send');
    var levels = form.querySelectorAll('.sv-level');
    var pstars = form.querySelectorAll('.sv-pstar');
    var groups = form.querySelectorAll('.sv-group');
    var chosen = 0, chosenStars = 0, chosenLabel = '', hover = 0;

    function paint() {
        var active = hover || chosenStars;
        levels.forEach(function (b) {
            var n = parseInt(b.dataset.stars, 10), on = parseInt(b.dataset.card, 10) === chosen;
            b.classList.toggle('on', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
            b.querySelector('.st').classList.toggle('lit', n <= active);
        });
        pstars.forEach(function (b) {
            var n = parseInt(b.dataset.stars, 10), on = parseInt(b.dataset.card, 10) === chosen;
            b.classList.toggle('lit', n <= active);
            b.classList.toggle('on', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        if (phoneLabel) {
            phoneLabel.textContent = chosen ? chosenLabel : 'Toca una estrella';
            phoneLabel.classList.toggle('set', !!chosen);
        }
    }

    function syncChips() {
        var kind = chosenStars >= 4 ? 'positive' : 'negative';
        groups.forEach(function (g) {
            var show = !!chosen && g.dataset.kind === kind;
            g.hidden = !show;
            g.querySelectorAll('input').forEach(function (i) { i.disabled = !show; });
        });
    }

    function paintChips() {
        form.querySelectorAll('[data-chip]').forEach(function (l) {
            var i = l.querySelector('input');
            l.classList.toggle('on', i.checked);
            l.querySelector('[data-mark]').textContent = i.checked ? '✓' : '+';
        });
    }

    function pick(id, stars, label) {
        var changedKind = (chosenStars >= 4) !== (stars >= 4);
        chosen = id; chosenStars = stars; chosenLabel = label;
        cardInput.value = id;
        if (changedKind) { form.querySelectorAll('.sv-chip input').forEach(function (i) { i.checked = false; }); }
        more.hidden = false;
        sendBtns.forEach(function (b) { b.disabled = false; });
        syncChips(); paintChips(); paint();
    }

    function cardLabel(btn) {
        var t = btn.querySelector('.tx');
        return btn.dataset.label || (t ? t.textContent.trim() : '');
    }

    levels.forEach(function (b) {
        b.addEventListener('click', function () { pick(parseInt(b.dataset.card, 10), parseInt(b.dataset.stars, 10), cardLabel(b)); });
        b.addEventListener('mouseenter', function () { hover = parseInt(b.dataset.stars, 10); paint(); });
    });
    var wrap = document.getElementById('svLevels');
    if (wrap) { wrap.addEventListener('mouseleave', function () { hover = 0; paint(); }); }
    pstars.forEach(function (b) {
        b.addEventListener('click', function () { pick(parseInt(b.dataset.card, 10), parseInt(b.dataset.stars, 10), cardLabel(b)); });
    });

    form.querySelectorAll('.sv-chip input').forEach(function (i) { i.addEventListener('change', paintChips); });

    function updateCount() { count.textContent = comment.value.length; }
    comment.addEventListener('input', updateCount); updateCount();

    form.addEventListener('submit', function (e) { if (!chosen) { e.preventDefault(); } });

    // Estado inicial (valores previos o calificación en edición)
    var initial = parseInt(cardInput.value, 10) || 0;
    if (initial) {
        var src = form.querySelector('.sv-level[data-card="' + initial + '"]') || form.querySelector('.sv-pstar[data-card="' + initial + '"]');
        if (src) {
            var keep = [];
            form.querySelectorAll('.sv-chip input:checked').forEach(function (i) { keep.push(i); });
            pick(initial, parseInt(src.dataset.stars, 10), cardLabel(src));
            keep.forEach(function (i) { if (!i.disabled) { i.checked = true; } });
            paintChips();
        }
    }
})();
</script>
@endunless
</body>
</html>
