@if (!empty($section->config['steps']))
<section class="svc-process" @if($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <h2 class="svc-process__title">{{ $section->title }}</h2>
    @endif
    <div class="svc-process__list">
        @foreach ($section->config['steps'] as $i => $step)
            <div class="svc-process__step">
                <div class="svc-process__num">{{ $i + 1 }}</div>
                <div class="svc-process__body">
                    @if (!empty($step['title']))
                        <p class="svc-process__step-title">{{ $step['title'] }}</p>
                    @endif
                    @if (!empty($step['description']))
                        <p class="svc-process__step-desc">{{ $step['description'] }}</p>
                    @endif
                </div>
                @if (!empty($step['duration']))
                    <span class="svc-process__duration">{{ $step['duration'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
