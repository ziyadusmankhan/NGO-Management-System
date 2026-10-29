@if (auth()->user()?->hasPermission('committees.delete'))
    <form method="POST" action="{{ route('committees.destroy', $committee) }}" style="display:inline-flex;margin:0;" onsubmit="return confirm('Archive this committee? It will leave the current committee list. Member records and membership history will be kept.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="{{ $buttonClass ?? 'details-btn' }}" style="color:var(--legacy-text-a13d3d, #a13d3d);border-color:var(--legacy-border-efcccc, #efcccc);" aria-label="Archive {{ $committee->name }}">
            <i class="bi bi-archive" aria-hidden="true"></i> Archive
        </button>
    </form>
@endif
