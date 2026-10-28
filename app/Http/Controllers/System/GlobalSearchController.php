<?php

namespace App\Http\Controllers\System;

use App\Domain\Search\Services\GlobalSearch;
use Illuminate\Http\Request;

class GlobalSearchController
{
    public function __invoke(Request $request, GlobalSearch $search)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:100']]);
        $term = trim((string) ($data['q'] ?? ''));
        $groups = $term === '' ? collect() : $search->run($request->user(), $term);
        $total = $groups->sum(fn ($group) => $group['results']->count());

        return response()->view('system.search.index', compact('term', 'groups', 'total'))
            ->header('Cache-Control', 'private, no-store');
    }
}
