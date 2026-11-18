{{-- Un bloque de texto formateado del PDF de cotización (ver App\Support\QuoteTextFormatter).
     $block['html'] ya viene escapado; solo contiene <strong>, <em> y <br>. --}}
@switch($block['type'])
    @case('h')
        <div class="blk-h">{!! $block['html'] !!}</div>
        @break
    @case('li')
        <div class="blk-li"><span class="mk">•</span>{!! $block['html'] !!}</div>
        @break
    @case('ol')
        <div class="blk-ol"><span class="mk">{{ $block['marker'] }}</span>{!! $block['html'] !!}</div>
        @break
    @case('kv')
        <div class="blk-kv">{!! $block['html'] !!}</div>
        @break
    @default
        <div class="blk-p">{!! $block['html'] !!}</div>
@endswitch
