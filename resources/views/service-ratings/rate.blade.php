<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $texts['survey.title'] }} - {{ $report->report_number }}</title>
    <style>
        :root{--bg:#f4f6fa;--surface:#fff;--ink:#111827;--muted:#6b7280;--line:#e5e7eb;--brand:#0f4c81;--brand-ink:#fff;--star:#f59e0b;--danger:#b91c1c;--radius:18px}
        @media (prefers-color-scheme:dark){:root{--bg:#0b1220;--surface:#131c2e;--ink:#f3f4f6;--muted:#9ca3af;--line:#27324a;--brand:#4f9be0;--brand-ink:#06121f}}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;background:var(--bg);color:var(--ink);font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;font-size:17px;line-height:1.45;-webkit-tap-highlight-color:transparent}
        .sv-wrap{max-width:860px;margin:0 auto;padding:24px 16px 48px}
        .sv-top{display:flex;justify-content:space-between;align-items:center;gap:12px;color:var(--muted);font-size:14px;margin-bottom:14px}
        .sv-top a{color:var(--muted)}
        .sv-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);padding:28px 22px;box-shadow:0 8px 28px rgba(15,23,42,.06)}
        h1{margin:0 0 6px;font-size:clamp(26px,5vw,36px);line-height:1.15;text-align:center}
        .sv-sub{margin:0 0 22px;text-align:center;color:var(--muted)}
        .sv-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px}
        .sv-opt{position:relative;display:block;cursor:pointer}
        .sv-opt input{position:absolute;opacity:0;inset:0;width:100%;height:100%;cursor:pointer;margin:0}
        .sv-opt-body{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:18px 10px;min-height:116px;border:2px solid var(--line);border-radius:16px;background:var(--surface);transition:transform .12s,border-color .12s,background .12s,box-shadow .12s;text-align:center}
        .sv-opt-label{font-weight:700}
        .sv-opt-stars{color:var(--star);letter-spacing:3px;font-size:24px;line-height:1}
        .sv-opt:hover .sv-opt-body{transform:translateY(-2px)}
        .sv-opt input:focus-visible+.sv-opt-body{outline:3px solid var(--brand);outline-offset:2px}
        .sv-opt input:checked+.sv-opt-body{border-color:var(--c,var(--brand));background:color-mix(in srgb,var(--c,var(--brand)) 14%,var(--surface));box-shadow:0 0 0 3px color-mix(in srgb,var(--c,var(--brand)) 35%,transparent);transform:scale(1.03)}
        .sv-opt-check{position:absolute;top:8px;right:10px;width:22px;height:22px;border-radius:50%;background:var(--c,var(--brand));color:#fff;display:none;align-items:center;justify-content:center;font-size:13px;font-weight:700}
        .sv-opt input:checked~.sv-opt-check{display:flex}
        .sv-survey{margin-top:26px;border-top:1px dashed var(--line);padding-top:22px}
        .js .sv-survey{display:none}
        .js .sv-survey.is-open{display:block;animation:svIn .28s ease both}
        @keyframes svIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
        @media (prefers-reduced-motion:reduce){.js .sv-survey.is-open{animation:none}.sv-opt-body{transition:none}}
        h2{font-size:20px;margin:0 0 4px}
        .sv-q{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid var(--line);flex-wrap:wrap}
        .sv-q-label{font-weight:600;flex:1 1 200px}
        .sv-stars{display:flex;gap:4px}
        .sv-star{position:relative;width:48px;height:48px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:34px;color:var(--line);transition:color .1s,transform .1s;user-select:none}
        .sv-star input{position:absolute;opacity:0;inset:0;width:100%;height:100%;margin:0;cursor:pointer}
        .sv-star.on{color:var(--star)}
        .sv-star:active{transform:scale(.9)}
        .sv-star:focus-within{outline:3px solid var(--brand);border-radius:10px}
        .sv-field{margin-top:20px}
        .sv-field label,.sv-lbl{display:block;font-weight:600;margin-bottom:6px}
        textarea,.sv-lock input{width:100%;font:inherit;color:inherit;background:var(--bg);border:1.5px solid var(--line);border-radius:12px;padding:12px 14px}
        textarea{min-height:110px;resize:vertical}
        textarea:focus{outline:3px solid color-mix(in srgb,var(--brand) 40%,transparent);border-color:var(--brand)}
        .sv-count{text-align:right;font-size:13px;color:var(--muted);margin-top:4px}
        .sv-lock{position:relative}
        .sv-lock input{padding-left:42px;opacity:.85;cursor:not-allowed}
        .sv-lock svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted)}
        .sv-actions{display:flex;gap:12px;margin-top:26px;flex-wrap:wrap;justify-content:center}
        .sv-btn{font:inherit;font-weight:700;border-radius:14px;border:2px solid transparent;padding:16px 28px;min-height:56px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
        .sv-btn-primary{background:var(--brand);color:var(--brand-ink);flex:1 1 260px;max-width:420px}
        .sv-btn-primary:disabled{opacity:.4;cursor:not-allowed}
        .sv-btn-ghost{background:transparent;color:var(--muted);border-color:var(--line)}
        .sv-err{background:color-mix(in srgb,var(--danger) 12%,var(--surface));color:var(--danger);border:1px solid var(--danger);border-radius:12px;padding:12px 14px;margin-bottom:16px}
        .sv-thanks{text-align:center;padding:12px 0}
        .sv-thanks .big{font-size:64px;line-height:1}
        .sv-sum{margin:18px auto 0;max-width:520px;text-align:left}
        .sv-sum li{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);list-style:none}
        .sv-sum ul{padding:0;margin:0}
        .sv-star-line{color:var(--star);letter-spacing:2px}
        .sv-comment{margin-top:14px;padding:12px 14px;background:var(--bg);border-radius:12px;color:var(--muted);text-align:left;white-space:pre-line}
    </style>
    <script>document.documentElement.className += ' js';</script>
</head>
<body>
<div class="sv-wrap">
    <div class="sv-top">
        <span>Reporte {{ $report->report_number }}</span>
        <a href="{{ $backUrl }}">Volver</a>
    </div>

    <div class="sv-card">
        @if ($done)
            <div class="sv-thanks" role="status">
                <div class="big" aria-hidden="true" style="color:var(--star);letter-spacing:4px">{{ str_repeat('★', max(1, (int) $rating->rating)) }}</div>
                <h1>{{ $justSubmitted ? '¡Gracias!' : 'Tu calificación' }}</h1>
                @if ($justSubmitted)<p class="sv-sub">{{ $texts['survey.thanks_message'] }}</p>@endif

                <div class="sv-sum">
                    <ul>
                        <li><span>{{ $texts['survey.name_label'] }}</span><strong>{{ $rating->customer_name }}</strong></li>
                        <li><span>{{ $rating->card_label }}</span><span class="sv-star-line" aria-label="{{ $rating->rating }} de 5 estrellas">{{ str_repeat('★', (int) $rating->rating) }}{{ str_repeat('☆', 5 - (int) $rating->rating) }}</span></li>
                        @foreach ($rating->answers as $a)
                            <li><span>{{ $a->question_label }}</span><span class="sv-star-line" aria-label="{{ $a->score }} de 5">{{ str_repeat('★', $a->score) }}{{ str_repeat('☆', 5 - $a->score) }}</span></li>
                        @endforeach
                    </ul>
                    @if ($rating->comment)<div class="sv-comment">{{ $rating->comment }}</div>@endif
                </div>
                <div class="sv-actions"><a class="sv-btn sv-btn-primary" href="{{ $backUrl }}">Continuar</a></div>
            </div>
        @else
            <form method="POST" action="{{ $action }}" id="svForm" novalidate>
                @csrf
                <h1>{{ $texts['survey.title'] }}</h1>
                <p class="sv-sub">{{ $texts['survey.subtitle'] }}</p>

                @if ($errors->any())
                    <div class="sv-err" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="sv-cards" role="radiogroup" aria-label="{{ $texts['survey.title'] }}">
                    @foreach ($cards as $card)
                        <label class="sv-opt" style="--c: {{ $card->color }}">
                            <input type="radio" name="card_id" value="{{ $card->id }}" @checked((int) old('card_id') === $card->id)>
                            <span class="sv-opt-body">
                                <span class="sv-opt-label">{{ $card->label }}</span>
                                <span class="sv-opt-stars" aria-hidden="true">{{ str_repeat('★', $card->stars) }}</span>
                            </span>
                            <span class="sv-opt-check" aria-hidden="true">✓</span>
                        </label>
                    @endforeach
                </div>

                <section class="sv-survey" id="svSurvey" aria-live="polite">
                    @if ($questions->isNotEmpty())
                        <h2>{{ $texts['survey.questions_title'] }}</h2>
                        <p class="sv-sub" style="text-align:left;margin-bottom:6px;">Toca las estrellas en lo que quieras calificar.</p>
                        @foreach ($questions as $q)
                            <div class="sv-q" data-q>
                                <span class="sv-q-label">{{ $q->label }}</span>
                                <div class="sv-stars" role="radiogroup" aria-label="{{ $q->label }}">
                                    @foreach (range(1, 5) as $n)
                                        <label class="sv-star" title="{{ $n }} de 5">
                                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $n }}" @checked((string) old('answers.' . $q->id) === (string) $n) aria-label="{{ $q->label }}: {{ $n }} de 5">
                                            <span aria-hidden="true">★</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div class="sv-field">
                        <label for="svComment">{{ $texts['survey.comment_label'] }}</label>
                        <textarea id="svComment" name="comment" maxlength="{{ $commentMax }}" placeholder="Escribe aquí si quieres agregar algo más…">{{ old('comment') }}</textarea>
                        <div class="sv-count"><span id="svCount">0</span>/{{ $commentMax }}</div>
                    </div>

                    <div class="sv-field">
                        <span class="sv-lbl">{{ $texts['survey.name_label'] }}</span>
                        <div class="sv-lock">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="text" value="{{ $rating->customer_name }}" readonly disabled aria-label="{{ $texts['survey.name_label'] }} (no editable)">
                        </div>
                    </div>
                </section>

                <div class="sv-actions">
                    <button type="submit" class="sv-btn sv-btn-primary" id="svSubmit">{{ $texts['survey.submit_label'] }}</button>
                    <button type="submit" class="sv-btn sv-btn-ghost" form="svSkipForm">Ahora no</button>
                </div>
            </form>
            <form method="POST" action="{{ $skipAction }}" id="svSkipForm">@csrf</form>
        @endif
    </div>
</div>

@unless ($done)
<script>
(function () {
    var form = document.getElementById('svForm');
    var survey = document.getElementById('svSurvey');
    var submit = document.getElementById('svSubmit');
    var comment = document.getElementById('svComment');
    var count = document.getElementById('svCount');

    function cardChosen() { return !!form.querySelector('input[name="card_id"]:checked'); }
    function sync() {
        var ok = cardChosen();
        submit.disabled = !ok;
        if (ok) { survey.classList.add('is-open'); }
    }
    function paintStars(group) {
        var checked = group.querySelector('input:checked');
        var v = checked ? parseInt(checked.value, 10) : 0;
        group.querySelectorAll('.sv-star').forEach(function (s, i) { s.classList.toggle('on', i < v); });
    }

    form.querySelectorAll('input[name="card_id"]').forEach(function (r) {
        r.addEventListener('change', function () {
            sync();
            setTimeout(function () { survey.scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest'}); }, 60);
        });
    });

    form.querySelectorAll('.sv-stars').forEach(function (group) {
        paintStars(group);
        var last = group.querySelector('input:checked');
        group.querySelectorAll('input').forEach(function (inp) {
            // tocar de nuevo la misma estrella borra la respuesta (cada variable es opcional)
            inp.addEventListener('click', function () {
                if (last === inp) { inp.checked = false; last = null; } else { last = inp; }
                paintStars(group);
            });
        });
    });

    function updateCount() { count.textContent = comment.value.length; }
    comment.addEventListener('input', updateCount); updateCount();

    form.addEventListener('submit', function (e) { if (!cardChosen()) { e.preventDefault(); } });
    sync();
})();
</script>
@endunless
</body>
</html>
