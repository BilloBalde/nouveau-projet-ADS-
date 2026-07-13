<div {{ $attributes->merge(['class' => 'form-actions']) }}>
    <button type="submit" class="btn btn-submit me-2">
        {{ $submitText }}
    </button>
    
    @if($cancelRoute)
        <a href="{{ $cancelRoute }}" class="btn btn-cancel">
            {{ $cancelText }}
        </a>
    @else
        <a href="{{ url()->previous() }}" class="btn btn-cancel">
            {{ $cancelText }}
        </a>
    @endif
</div>