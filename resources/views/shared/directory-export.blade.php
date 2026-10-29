@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/organization-exports.css') }}">@endpush
@push('scripts')<script>
(() => {
    document.addEventListener('keydown', event => { if(event.key === 'Escape') document.querySelectorAll('.directory-export-menu[open]').forEach(menu => { menu.open = false; menu.querySelector('summary').focus(); }); });
    document.addEventListener('click', event => document.querySelectorAll('.directory-export-menu[open]').forEach(menu => { if(!menu.contains(event.target)) menu.open = false; }));
})();
</script>@endpush
@endonce
<details class="directory-export-menu">
    <summary><i class="bi bi-download" aria-hidden="true"></i> Export <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
    <div class="directory-export-options">
        <span>All matching records</span>
        <a href="{{ route($exportRoute, array_merge(request()->only(['search','status','type','role']), ['format'=>'pdf'])) }}"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download PDF</a>
        <a href="{{ route($exportRoute, array_merge(request()->only(['search','status','type','role']), ['format'=>'xlsx'])) }}"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Download Excel (.xlsx)</a>
    </div>
</details>
