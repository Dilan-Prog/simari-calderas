{{-- Lista de items de actividad -- compartida entre la tarjeta con scroll y el modal "Ver detalles". Espera $activities. --}}
@foreach($activities as $activity)
    <div style="display:flex;gap:10px;padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #F3F4F6;' : '' }}">
        <div style="flex-shrink:0;width:8px;height:8px;border-radius:50%;margin-top:5px;background:{{ $activity['type'] === 'manual' ? '#ff6213' : '#4338ca' }};"></div>
        <div style="min-width:0;">
            <div style="font-size:12.5px;font-weight:600;color:#141516;">{{ $activity['label'] }}</div>
            @if($activity['detail'])
                <div style="font-size:11.5px;color:#6b7280;margin-top:1px;">{{ $activity['detail'] }}</div>
            @endif
            <div style="font-size:11px;color:#9CA3AF;margin-top:2px;">{{ $activity['at']?->format('d/m/Y H:i') }}</div>
            @if(!empty($activity['preview']))
                <button type="button"
                        onclick="openActivityPreview('{{ $activity['preview'] }}', {{ $activity['preview_id'] ?? 'null' }})"
                        style="margin-top:5px;border:none;background:transparent;color:#ff6213;font-size:11.5px;font-weight:700;cursor:pointer;padding:0;">
                    Vista previa →
                </button>
            @endif
        </div>
    </div>
@endforeach
