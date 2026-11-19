@php
    /**
     * Bloque "Título y párrafo" (text_block): un encabezado opcional y uno o
     * varios párrafos. Una línea en blanco separa párrafos; un salto simple
     * se respeta como <br>. Admite enlaces en línea con [texto](/ruta)
     * (App\Support\LinkText) y el estilo tipográfico de App\Support\TextStyle.
     *
     * config: heading, heading_style{heading_tag,text_align,...}, body, body_style
     */
    $config = $section->config ?? [];
    $tbContext = $servicePage ?? $collection ?? $contextCategory ?? null;

    $tbHeading = trim((string) ($config['heading'] ?? ''));
    if ($tbHeading !== '' && method_exists($section, 'resolveText')) {
        $tbHeading = $section->resolveText($tbHeading, $tbContext);
    }

    $tbBody = str_replace(["\r\n", "\r"], "\n", trim((string) ($config['body'] ?? '')));
    if ($tbBody !== '' && method_exists($section, 'resolveText')) {
        // resolveText colapsa espacios dobles (incluidos saltos): se aplica por párrafo/renglón.
        $tbBody = implode("\n", array_map(fn ($l) => $l === '' ? '' : $section->resolveText($l, $tbContext), explode("\n", $tbBody)));
    }
    $tbParagraphs = $tbBody === '' ? [] : array_values(array_filter(preg_split("/\n\s*\n/", $tbBody), fn ($p) => trim($p) !== ''));

    $tbHeadingStyle = $config['heading_style'] ?? null;
    $tbHeadingTag = \App\Support\TextStyle::tag($tbHeadingStyle, 'h2', ['h1', 'h2', 'h3', 'h4']);
    $tbBodyAttr = \App\Support\TextStyle::attr($config['body_style'] ?? null);
@endphp

@if ($tbHeading !== '' || $tbParagraphs)
<section class="text-block-section" data-section-id="{{ $section->id ?? '' }}">
    <div class="text-block-section__inner">
        @if ($tbHeading !== '')
            <{{ $tbHeadingTag }} class="text-block-section__title"{!! \App\Support\TextStyle::attr($tbHeadingStyle) !!}>{!! \App\Support\LinkText::render($tbHeading) !!}</{{ $tbHeadingTag }}>
        @endif
        @foreach ($tbParagraphs as $tbParagraph)
            <p class="text-block-section__text"{!! $tbBodyAttr !!}>{!! implode('<br>', array_map(fn ($l) => \App\Support\LinkText::render(trim($l)), explode("\n", trim($tbParagraph)))) !!}</p>
        @endforeach
    </div>
</section>
@elseif ($previewMode ?? false)
<section class="text-block-section" data-section-id="{{ $section->id ?? '' }}">
    <div class="text-block-section__inner"><p class="text-block-section__text" style="color:#9ca3af;">Escribe un título o un párrafo para este bloque.</p></div>
</section>
@endif
