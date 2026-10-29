@if (auth()->user()?->hasPermission('users.manage'))
    @if (auth()->id() === $user->id)
        <button type="button" class="{{ $buttonClass ?? 'btn-ui' }}" disabled title="You cannot archive your own account" style="opacity:.55;cursor:not-allowed;">
            <i class="bi bi-archive" aria-hidden="true"></i> Archive
        </button>
    @else
        <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline-flex;margin:0;" onsubmit="return confirm('Archive this user? They will no longer be able to sign in. Their existing records will be kept.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="{{ $buttonClass ?? 'btn-ui' }}" style="color:var(--legacy-text-a13d3d, #a13d3d);border-color:var(--legacy-border-efcccc, #efcccc);" aria-label="Archive {{ $user->name }}">
                <i class="bi bi-archive" aria-hidden="true"></i> Archive
            </button>
        </form>
    @endif
@endif
