{{-- Medios de contacto del lead: WhatsApp / telefono / correo (encendido si existe) y "3/3" si tiene los tres. Variables: $lead, $icon --}}
@php
    $cc = \App\Support\LeadChannels::for($lead);
    $ccLabel = \App\Support\LeadChannels::label($cc);
@endphp
<span class="lw-chs" title="{{ $ccLabel }}" aria-label="{{ $ccLabel }}">
    <i class="lw-chb lw-chb--wa {{ $cc['whatsapp'] ? 'is-on' : '' }}">{!! $icon('whatsapp') !!}</i>
    <i class="lw-chb lw-chb--tel {{ $cc['phone'] ? 'is-on' : '' }}">{!! $icon('llamada') !!}</i>
    <i class="lw-chb lw-chb--mail {{ $cc['email'] ? 'is-on' : '' }}">{!! $icon('correo') !!}</i>
    @if ($cc['count'] === 3)<span class="lw-chs-all">3/3</span>@endif
</span>
