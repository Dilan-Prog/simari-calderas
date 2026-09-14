@php
    $config = $section->config ?? [];
    $headers = $config['headers'] ?? [];
    $rows = $config['rows'] ?? [];
    $buttons = $config['buttons'] ?? [];
    $align = in_array($config['align'] ?? 'left', ['left', 'center', 'right'], true) ? $config['align'] : 'left';
@endphp
@if (!empty($headers) && !empty($rows))
<section class="svc-table-block" @if ($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <h2 class="svc-table-block__title">{{ $section->title }}</h2>
    @endif
    @if (!empty($config['description']))
        <p class="svc-table-block__desc">{{ $config['description'] }}</p>
    @endif

    <div class="svc-table-block__scroll">
        <table class="svc-table-block__table">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $cells)
                    <tr>
                        @foreach ($headers as $i => $header)
                            <td>{{ $cells[$i] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (!empty($buttons))
        <div class="svc-table-block__actions svc-table-block__actions--{{ $align }}">
            @foreach ($buttons as $btn)
                @continue(empty($btn['text']) || empty($btn['url']))
                @php
                    $style = ($btn['style'] ?? 'solid') === 'outline' ? 'outline' : 'solid';
                    $color = $btn['color'] ?? '#ff6213';
                @endphp
                <a href="{{ $btn['url'] }}" class="svc-btn svc-btn--{{ $style }}"
                   style="{{ $style === 'solid' ? 'background:'.e($color).';border-color:'.e($color) : 'color:'.e($color).';border-color:'.e($color) }}"
                   @if (!str_starts_with($btn['url'], '/')) target="_blank" rel="noopener" @endif>
                    {{ $btn['text'] }}
                </a>
            @endforeach
        </div>
    @endif
</section>
@endif
