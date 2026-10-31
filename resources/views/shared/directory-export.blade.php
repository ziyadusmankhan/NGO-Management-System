@once
@push('styles')<link rel="stylesheet" href="{{ asset('css/organization-exports.css') }}">@endpush
@push('scripts')<script>
(() => {
    const fitMenu = menu => {
        if(!menu.open) return;
        const options = menu.querySelector('.directory-export-options');
        options.style.transform = '';
        const rect = options.getBoundingClientRect();
        const shift = rect.left < 16 ? 16 - rect.left : rect.right > innerWidth - 16 ? innerWidth - 16 - rect.right : 0;
        if(shift) options.style.transform = `translateX(${shift}px)`;
    };
    document.addEventListener('toggle', event => { if(event.target.matches('.directory-export-menu')) fitMenu(event.target); }, true);
    addEventListener('resize', () => document.querySelectorAll('.directory-export-menu[open]').forEach(fitMenu));
    document.addEventListener('keydown', event => { if(event.key === 'Escape') document.querySelectorAll('.directory-export-menu[open]').forEach(menu => { menu.open = false; menu.querySelector('summary').focus(); }); });
    document.addEventListener('click', event => document.querySelectorAll('.directory-export-menu[open]').forEach(menu => { if(!menu.contains(event.target)) menu.open = false; }));
})();
</script>@endpush
@endonce
<details class="directory-export-menu">
    <summary><i class="bi bi-download" aria-hidden="true"></i> {{ $exportLabel ?? 'Export' }} <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
    <div class="directory-export-options">
        <span>All matching records</span>
        <a href="{{ route($exportRoute, array_merge(request()->only($exportFilters ?? ['search','status','type','role']), $exportParameters ?? [], ['format'=>'pdf'])) }}"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download PDF</a>
        <a href="{{ route($exportRoute, array_merge(request()->only($exportFilters ?? ['search','status','type','role']), $exportParameters ?? [], ['format'=>'xlsx'])) }}"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Download Excel (.xlsx)</a>
    </div>
</details>
