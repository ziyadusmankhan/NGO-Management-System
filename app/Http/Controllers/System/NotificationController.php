<?php

namespace App\Http\Controllers\System;

use App\Domain\Notifications\Services\NotificationCenter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class NotificationController
{
    public function index(Request $request, NotificationCenter $center)
    {
        $filters = $request->validate([
            'scope' => ['nullable', Rule::in(['all', 'unread', 'dismissed'])],
            'module' => ['nullable', 'string', 'max:40'],
        ]);
        $scope = $filters['scope'] ?? 'all';
        $items = $center->items($request->user(), $scope === 'dismissed')
            ->when($scope === 'unread', fn ($items) => $items->whereNull('read_at'))
            ->when($scope === 'dismissed', fn ($items) => $items->whereNotNull('dismissed_at'))
            ->when(! empty($filters['module']), fn ($items) => $items->where('module', $filters['module']))
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $notifications = new LengthAwarePaginator($items->forPage($page, 20)->values(), $items->count(), 20, $page, [
            'path' => route('notifications.index'), 'query' => $request->query(),
        ]);
        $modules = $center->items($request->user(), true)->pluck('module')->unique()->sort()->values();
        $unread = $center->unreadCount($request->user());

        return response()->view('system.notifications.index', compact('notifications', 'modules', 'filters', 'scope', 'unread'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function change(Request $request, NotificationCenter $center)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:190'],
            'action' => ['required', Rule::in(['read', 'unread', 'dismiss', 'restore'])],
        ]);
        $center->change($request->user(), $data['key'], $data['action']);

        return back()->with('success', 'Notification updated.');
    }

    public function readAll(Request $request, NotificationCenter $center)
    {
        $center->markAllRead($request->user());

        return back()->with('success', 'All current notifications marked as read.');
    }
}
